#!/usr/bin/env python3
import json
import os
import urllib.error
import urllib.request
from dataclasses import dataclass
from typing import Any, Dict, Optional, Tuple


@dataclass
class SmokeClient:
    base_url: str
    admin_user: str
    admin_password: str
    token: Optional[str] = None

    def call(
        self,
        method: str,
        path: str,
        data: Optional[Dict[str, Any]] = None,
        token: Optional[str] = None,
        content_type: str = "application/json",
    ) -> Tuple[int, Any]:
        body = None
        headers: Dict[str, str] = {}
        if data is not None:
            body = json.dumps(data, ensure_ascii=False).encode("utf-8")
            headers["Content-Type"] = content_type
        auth_token = token if token is not None else self.token
        if auth_token:
            headers["Authorization"] = f"Bearer {auth_token}"

        request = urllib.request.Request(
            self.base_url.rstrip("/") + path,
            data=body,
            headers=headers,
            method=method.upper(),
        )
        try:
            with urllib.request.urlopen(request) as response:
                raw = response.read().decode("utf-8")
                return response.status, json.loads(raw) if raw else {}
        except urllib.error.HTTPError as exc:
            raw = exc.read().decode("utf-8")
            try:
                payload = json.loads(raw)
            except Exception:
                payload = {"raw": raw}
            return exc.code, payload

    def login(self) -> str:
        status, payload = self.call(
            "POST",
            "/api/auth/login",
            {"username": self.admin_user, "password": self.admin_password},
        )
        if status != 200 or "token" not in payload:
            raise RuntimeError(f"Auth failed: status={status}, payload={payload}")
        self.token = payload["token"]
        return self.token


def env_client() -> SmokeClient:
    return SmokeClient(
        base_url=os.getenv("API_BASE_URL", "http://127.0.0.1:8000"),
        admin_user=os.getenv("API_ADMIN_USER", "admin"),
        admin_password=os.getenv("API_ADMIN_PASSWORD", "admin123"),
    )

