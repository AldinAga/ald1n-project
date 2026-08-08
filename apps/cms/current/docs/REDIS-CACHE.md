# Redis je isključen u v2.0.0-beta6

Production Orders & Inventory izdanje ne koristi Redis.

Obavezna produkciona podešavanja:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
CACHE_LIMITER=file
QUEUE_CONNECTION=sync
```

`CACHE_LIMITER=file` osigurava da login rate limiter ne pokuša Redis konekciju. Queue radi sinhrono, bez queue worker-a i bez implicitne Redis zavisnosti.

File režim zahteva upisive direktorijume:

```text
storage/framework/sessions
storage/framework/cache/data
storage/framework/views
storage/logs
bootstrap/cache
```

Beta6 ih čuva u paketu pomoću `.gitignore` placeholder-a, proverava ih globalnim middleware-om pre session middleware-a i može ih popraviti komandom:

```bash
php artisan app:deployment-check --repair
```

Ako direktorijumi ne mogu da se kreiraju ili nisu upisivi, aplikacija vraća kontrolisani HTTP 503 sa administratorskom porukom umesto neobjašnjenog HTTP 500.

Redis konekcije i store-ovi ostaju uklonjeni iz produkcione konfiguracije. Redis se ne uključuje ručno u ovom release-u.
