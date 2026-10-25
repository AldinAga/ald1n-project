#!/usr/bin/env python3
"""B854/B855: Laravel route/method/middleware inventory, not a pentest.

Consumes exact-source PHP artisan route:list --json output in disposable CI.
No HTTP, credentials, production access, bypass, or OWASP PASS claims.
"""
from __future__ import annotations
import argparse
import hashlib
import json
import re
from pathlib import Path

CONTRACT = {
    "public-shop.catalog": ("GET|HEAD", "katalog", "PublicShopController@catalog", "throttle:public-shop-browse"),
    "public-shop.media": ("GET|HEAD", "katalog/slika/{image}", "PublicShopController@media", "throttle:public-shop-images"),
    "public-shop.show": ("GET|HEAD", "katalog/{slug}", "PublicShopController@show", "throttle:public-shop-browse"),
    "public-shop.cart": ("GET|HEAD", "korpa", "PublicShopController@cart", "throttle:public-shop-browse"),
    "public-shop.checkout": ("GET|HEAD", "porucivanje", "PublicCheckoutController@form", "throttle:public-shop-browse"),
    "public-shop.checkout.submit": ("POST", "porucivanje", "PublicCheckoutController@store", "throttle:public-shop-checkout"),
    "public-shop.order.show": ("GET|HEAD", "porudzbina/{token}", "PublicOrderAccessController@show", "throttle:public-shop-private"),
    "public-shop.order.document": ("GET|HEAD", "porudzbina/{token}/dokument/{document}", "PublicOrderAccessController@document", "throttle:public-shop-private"),
    "public-shop.order.warranty": ("GET|HEAD", "porudzbina/{token}/garancija/{issuance}", "PublicOrderAccessController@warranty", "throttle:public-shop-private"),
    "public-shop.cart.add": ("POST", "korpa/dodaj/{product}", "PublicShopController@add", "throttle:public-shop-cart-write"),
    "public-shop.cart.update": ("PATCH", "korpa/{product}", "PublicShopController@update", "throttle:public-shop-cart-write"),
    "public-shop.cart.remove": ("DELETE", "korpa/{product}", "PublicShopController@remove", "throttle:public-shop-cart-write"),
    "public-shop.register": ("GET|HEAD", "registracija", "PublicBuyerRegistrationController@create", "throttle:public-shop-browse"),
    "public-shop.register.submit": ("POST", "registracija", "PublicBuyerRegistrationController@store", "throttle:5,10"),
}
PUBLIC_PREFIXES = ("katalog","korpa","porucivanje","porudzbina","registracija")
SHA_RE = re.compile("^[0-9a-f]{40}$")

def methods(raw: str) -> set[str]:
    parts=set(raw.split("|"))
    if not parts or any(re.fullmatch("[A-Z]+",p) is None for p in parts):
        raise ValueError("HOLD_BAD_METHOD")
    return parts

def inventory(raw: bytes, source_sha: str) -> dict:
    if SHA_RE.fullmatch(source_sha) is None:
        raise ValueError("HOLD_UNPINNED_HEAD")
    payload=json.loads(raw)
    if not isinstance(payload,list) or not payload:
        raise ValueError("HOLD_EMPTY_ROUTE_LIST")
    visited={}
    normalized=[]
    for row in payload:
        if not isinstance(row,dict):
            raise ValueError("HOLD_BAD_ROUTE_SHAPE")
        name=row.get("name")
        uri=row.get("uri")
        action=row.get("action")
        middleware=row.get("middleware")
        method=row.get("method")
        if (not isinstance(name,(str,type(None))) or not isinstance(uri,str)
                or not isinstance(action,str) or not isinstance(method,str)
                or not isinstance(middleware,list)):
            raise ValueError("HOLD_MALFORMED_ROUTE")
        if any(not isinstance(mid,str) for mid in middleware):
            raise ValueError("HOLD_MALFORMED_MIDDLEWARE")
        uri=uri.lstrip("/")
        active_methods=methods(method)
        if name in CONTRACT:
            if name in visited:
                raise ValueError("HOLD_DUPLICATE_EXPECTED_ROUTE")
            expected_method,expected_uri,expected_action,required_middleware=CONTRACT[name]
            if active_methods!=methods(expected_method) or uri!=expected_uri:
                raise ValueError("HOLD_UNEXPECTED_PUBLIC_METHOD_OR_URI_"+name)
            if not action.endswith("\\"+expected_action) and action!=expected_action:
                raise ValueError("HOLD_UNEXPECTED_CONTROLLER_"+name)
            if required_middleware not in middleware or "web" not in middleware:
                raise ValueError("HOLD_PUBLIC_ROUTE_MIDDLEWARE_"+name)
            if name.startswith("public-shop.register") and "guest" not in middleware:
                raise ValueError("HOLD_SIGNUP_GUEST_BOUNDARY")
            visited[name]=True
        elif uri.split("/",1)[0] in PUBLIC_PREFIXES or (
                isinstance(name,str) and name.startswith("public-shop.")):
            raise ValueError("HOLD_UNREVIEWED_NEW_PUBLIC_ENDPOINT")
        normalized.append({
            "method":"|".join(sorted(active_methods)),
            "uri":uri,
            "name":name,
            "action":action,
            "middleware":sorted(set(middleware)),
        })
    missing=set(CONTRACT)-set(visited)
    if missing:
        raise ValueError("HOLD_MISSING_PUBLIC_ROUTE_"+",".join(sorted(missing)))
    return {
        "target_commit":source_sha,
        "raw_route_list_sha256":hashlib.sha256(raw).hexdigest(),
        "status":"STATIC_ROUTE_INVENTORY_ONLY",
        "public_contract_total":len(CONTRACT),
        "source_routes_total":len(normalized),
        "public_contract_names":sorted(visited),
        "limitations":"Checkout/receipt buyer auth lives in controllers/services; routes alone do not prove authorization. Isolated HTTP and independent security review still required.",
        "scope_workstreams":["SEC01","SEC04","SEC07","SEC21","SEC22"],
        "reviewed_owasp_items":0,
        "verified_owasp_security_pass":0,
        "wstg_asvs_368_item_register":"UNASSESSED_NOT_RUN",
        "full_security_test":"NOT_RUN",
        "release":"HOLD",
        "production_mutated":False,
        "routes":sorted(normalized,key=lambda r:(r["uri"],r["method"],str(r["name"]))),
    }

def main() -> None:
    parser=argparse.ArgumentParser()
    parser.add_argument("--routes",required=True,type=Path)
    parser.add_argument("--source-sha",required=True)
    parser.add_argument("--output",required=True,type=Path)
    args=parser.parse_args()
    result=inventory(args.routes.read_bytes(),args.source_sha)
    args.output.write_text(json.dumps(result,sort_keys=True,indent=2,ensure_ascii=False)+"\n",encoding="utf-8")
    print("B854_B855_ROUTE_INVENTORY="+str(result["public_contract_total"])+"_EXPECTED_PUBLIC_ENDPOINTS_STATIC_ONLY")
    print("B855_ALL_OWASP_368_ITEMS=UNASSESSED_NOT_RUN_SECURITY_PASS_ZERO")

if __name__=="__main__":
    main()
