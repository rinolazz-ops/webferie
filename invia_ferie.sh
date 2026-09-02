#!/usr/bin/env bash
set -euo pipefail

# ============================================================
# invia_ferie.sh
# Ogni sera controlla se "oggi + 30 giorni" è una delle date
# ferie desiderate (date-ferie.json). Se sì, invia la richiesta
# alle 22:00 ora italiana; altrimenti non fa nulla.
# ============================================================

ORA_TARGET="22"
FORZA="${FORZA:-false}"

ORA_ROMA=$(TZ='Europe/Rome' date +%H)
DATA_ROMA=$(TZ='Europe/Rome' date +%F)

if [ "$FORZA" != "true" ] && [ "$ORA_ROMA" != "$ORA_TARGET" ]; then
  echo "Non sono ancora le ${ORA_TARGET}:00 in Italia (adesso: ${ORA_ROMA}:00 Europe/Rome). Esco senza inviare nulla."
  exit 0
fi

DATA_FERIE=$(TZ='Europe/Rome' date -d "${DATA_ROMA} +30 days" +%F)
DATA_ISO=$(TZ='Europe/Rome' date -d "${DATA_FERIE} 00:00:00" -u +"%Y-%m-%dT%H:%M:%S.000Z")

# --- Controllo che la data calcolata sia tra quelle desiderate ---
if [ "$FORZA" != "true" ]; then
  if [ ! -f date-ferie.json ]; then
    echo "File date-ferie.json non trovato nel repo. Nessuna data programmata, esco."
    exit 0
  fi

  TARGET_IN_LIST=$(jq -r --arg d "$DATA_FERIE" '(.date // []) | index($d) != null' date-ferie.json 2>/dev/null || echo "false")

  if [ "$TARGET_IN_LIST" != "true" ]; then
    echo "La data ${DATA_FERIE} (oggi + 30 giorni) non è tra le ferie desiderate in date-ferie.json. Nessun invio oggi."
    exit 0
  fi

  echo "Trovata corrispondenza: ${DATA_FERIE} è nell'elenco delle ferie desiderate."
fi

if [ -z "${MYSPRISS_JWT:-}" ]; then
  echo "::error::Il secret MYSPRISS_JWT non è impostato. Vai su Settings > Secrets and variables > Actions e crealo."
  exit 1
fi

# --- Controllo scadenza del token (claim "exp" del JWT) ---
PAYLOAD_B64=$(echo "$MYSPRISS_JWT" | cut -d. -f2)
MOD=$(( ${#PAYLOAD_B64} % 4 ))
if [ "$MOD" -ne 0 ]; then
  PAYLOAD_B64="${PAYLOAD_B64}$(printf '=%.0s' $(seq 1 $((4 - MOD))))"
fi

EXP=$(echo "$PAYLOAD_B64" | base64 -d 2>/dev/null | jq -r '.exp // empty' || true)
NOW_EPOCH=$(date +%s)

if [ -z "$EXP" ]; then
  echo "::warning::Non riesco a leggere la scadenza dal token, procedo comunque."
else
  if [ "$NOW_EPOCH" -ge "$EXP" ]; then
    SCADENZA=$(date -u -d "@$EXP" +"%Y-%m-%d %H:%M UTC")
    echo "::error::Il token JWT è SCADUTO (scadenza: $SCADENZA). Rilancia estrai-token.js dal tuo PC per rinnovarlo."
    exit 1
  fi
  ORE_RIMASTE=$(( (EXP - NOW_EPOCH) / 3600 ))
  echo "Token OK, ancora valido per circa ${ORE_RIMASTE} ore."
fi

echo "Data ferie: ${DATA_FERIE} (invio payload UTC: ${DATA_ISO})"

PAYLOAD=$(jq -n --arg d "$DATA_ISO" '{
  IdCausal: 2,
  PunctualDate: $d,
  NewPunctualDate: $d,
  RangeDateFrom: null,
  RangeDateTo: null,
  NewRangeDateFrom: null,
  NewRangeDateTo: null,
  ChoiceSelected: []
}')

HTTP_CODE=$(curl -s -o /tmp/risposta.json -w "%{http_code}" \
  -X POST 'https://frontendmyspriss.avmspa.it/api/RequestApi/SaveRequest' \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer ${MYSPRISS_JWT}" \
  -H 'Origin: https://spriss.avmspa.it' \
  -H 'Referer: https://spriss.avmspa.it/' \
  -d "$PAYLOAD")

echo "HTTP status: ${HTTP_CODE}"
echo "Risposta server:"
cat /tmp/risposta.json
echo ""

# Il portale può rispondere HTTP 200 anche quando la richiesta è stata
# rifiutata a livello applicativo: bisogna controllare anche IsSuccessful.
IS_SUCCESSFUL=$(jq -r '.IsSuccessful // false' /tmp/risposta.json 2>/dev/null || echo "false")

if [ "$HTTP_CODE" != "200" ] || [ "$IS_SUCCESSFUL" != "true" ]; then
  ERROR_MSG=$(jq -r '.ErrorMessage // "nessun dettaglio fornito"' /tmp/risposta.json 2>/dev/null || echo "nessun dettaglio fornito")
  echo "::error::Richiesta ferie fallita (HTTP ${HTTP_CODE}, IsSuccessful=${IS_SUCCESSFUL}). Messaggio: ${ERROR_MSG}"
  exit 1
fi

echo "✅ Richiesta ferie per il ${DATA_FERIE} inviata con successo."
