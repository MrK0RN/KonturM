#!/bin/sh
set -e
LOCAL="/etc/caddy/Caddyfile.local"
OUT="/etc/caddy/Caddyfile"

if [ -n "$CADDY_DOMAIN" ]; then
	# Публичное имя: Caddy запрашивает Let's Encrypt (ACME HTTP-01 на :80).
	# Не используем tls internal — нужен DNS A/AAAA на этот сервер и открытые 80/443.
	: >"$OUT"
	if [ -n "$ACME_EMAIL" ]; then
		printf '{\n\temail %s\n}\n\n' "$ACME_EMAIL" >>"$OUT"
	fi
	{
		printf '%s {\n' "$CADDY_DOMAIN"
		printf '\tencode gzip zstd\n'
		printf '\treverse_proxy app:8000 {\n'
		printf '\t\theader_up Host {host}\n'
		printf '\t\theader_up X-Forwarded-Proto {scheme}\n'
		printf '\t\theader_up X-Forwarded-For {remote_host}\n'
		printf '\t\theader_up X-Forwarded-Host {host}\n'
		printf '\t}\n'
		printf '}\n'
	} >>"$OUT"
else
	cp "$LOCAL" "$OUT"
fi

exec caddy run --config "$OUT" --adapter caddyfile
