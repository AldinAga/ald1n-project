<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Neutral Serbian default texts for the public legal pages. They are a
 * starting template: the owner and a lawyer must review them before the
 * storefront opens. Article numbers are cited only where they were checked
 * against the published law text.
 *
 * Markup: "## " and "### " headings, "- " and "1. " lists, **bold**,
 * [text](https://...) links and {{placeholder}} values. Everything else is
 * escaped by LegalTextRenderer.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalTemplates
{
    /** Pages whose body the owner may edit. The trader page is generated. */
    public const EDITABLE = ['terms', 'privacy', 'withdrawal', 'complaints', 'delivery', 'cookies'];

    /** @var array<string,array{slug:string,title:string,summary:string}> */
    public const PAGES = [
        'trader' => ['slug' => 'podaci-o-trgovcu', 'title' => 'Podaci o trgovcu',
            'summary' => 'Ko prodaje, gde je sedište i kako nas kontaktirate.'],
        'terms' => ['slug' => 'uslovi-prodaje', 'title' => 'Opšti uslovi prodaje',
            'summary' => 'Kako se poručuje, kada je ugovor zaključen i koja pravila važe.'],
        'privacy' => ['slug' => 'politika-privatnosti', 'title' => 'Politika privatnosti',
            'summary' => 'Koje podatke obrađujemo, zašto i koja su vaša prava.'],
        'withdrawal' => ['slug' => 'pravo-na-odustanak', 'title' => 'Pravo na odustanak od ugovora',
            'summary' => 'Rok od 14 dana, postupak i obrazac za odustanak.'],
        'complaints' => ['slug' => 'reklamacije', 'title' => 'Reklamacije i saobraznost robe',
            'summary' => 'Kako izjavljujete reklamaciju i u kojim rokovima odgovaramo.'],
        'delivery' => ['slug' => 'dostava-i-placanje', 'title' => 'Dostava i plaćanje',
            'summary' => 'Načini dostave, cena dostave i načini plaćanja.'],
        'cookies' => ['slug' => 'kolacici', 'title' => 'Kolačići',
            'summary' => 'Koje kolačiće sajt koristi i zašto.'],
    ];

    /** @var array<string,string> placeholder => what it shows */
    public const PLACEHOLDERS = [
        'naziv_firme' => 'Poslovno ime trgovca (PDF i fakturisanje)',
        'adresa' => 'Adresa sedišta',
        'mesto' => 'Mesto sedišta',
        'pib' => 'PIB',
        'maticni_broj' => 'Matični broj',
        'telefon' => 'Telefon firme',
        'email_firme' => 'E-pošta firme',
        'email_podrske' => 'E-pošta podrške kupcima (Javna prodavnica)',
        'adresa_za_reklamacije' => 'Adresa i mesto za prijem reklamacija',
        'nacini_placanja' => 'Odabrani načini plaćanja',
        'nacini_dostave' => 'Odabrani načini dostave',
        'uslovi_dostave' => 'Opis uslova dostave',
        'cena_dostave' => 'Cena dostave u RSD',
        'internet_adresa' => 'Adresa sajta (APP_URL)',
    ];

    public static function body(string $page): string
    {
        return match ($page) {
            'terms' => self::TERMS,
            'privacy' => self::PRIVACY,
            'withdrawal' => self::WITHDRAWAL,
            'complaints' => self::COMPLAINTS,
            'delivery' => self::DELIVERY,
            'cookies' => self::COOKIES,
            default => throw new \InvalidArgumentException('Unknown legal page.'),
        };
    }

    private const TERMS = <<<'TXT'
## Prodavac
Prodavac je {{naziv_firme}}, {{adresa}}, {{mesto}}, PIB {{pib}}, matični broj {{maticni_broj}}. Kontakt: {{telefon}}, {{email_podrske}}.

## Na koga se uslovi odnose
Ovi uslovi važe za kupovinu u internet prodavnici na adresi {{internet_adresa}}. Kupac je fizičko lice koje kupuje za ličnu upotrebu i ima registrovan i aktiviran nalog Kupca.

## Porudžbina
1. Artikle dodajete u korpu.
2. Porudžbinu šaljete kao prijavljeni Kupac.
3. Pre slanja vidite artikle, ukupnu cenu, način dostave i način plaćanja.
4. Slanjem porudžbine prihvatate obavezu plaćanja.

Ugovor je zaključen kada porudžbinu primimo i potvrdimo. Podatke o porudžbini dobijate na e-poštu naloga.

## Cene
Cene su u dinarima (RSD) i važe u trenutku slanja porudžbine. Cena dostave: {{cena_dostave}}.

## Plaćanje i dostava
Načini plaćanja: {{nacini_placanja}}.
Načini dostave: {{nacini_dostave}}.

Ako nije drugačije dogovoreno, isporuku vršimo u roku od 30 dana od zaključenja ugovora (Zakon o zaštiti potrošača, član 31). Ako isporuka nije moguća, odmah vas obaveštavamo.

## Pravo na odustanak
Od ugovora možete odustati u roku od 14 dana, bez navođenja razloga. Postupak i obrazac su na stranici „Pravo na odustanak od ugovora”.

## Saobraznost i reklamacije
Odgovaramo za nesaobraznost robe koja se pojavi u roku od dve godine od dana kada je rizik prešao na vas. Postupak je na stranici „Reklamacije i saobraznost robe”.

## Podaci o ličnosti
Podatke obrađujemo prema Politici privatnosti.

## Rešavanje sporova
Spor najpre pokušavamo da rešimo dogovorom. Ako odbijemo reklamaciju, možete pokrenuti vansudsko rešavanje potrošačkog spora pred telom sa liste koju objavljuje ministarstvo nadležno za zaštitu potrošača. Po zakonu smo obavezni da učestvujemo u tom postupku (Zakon o zaštiti potrošača, član 151).

## Merodavno pravo
Primenjuje se pravo Republike Srbije, a naročito Zakon o zaštiti potrošača („Službeni glasnik RS”, broj 88/2021).
TXT;

    private const PRIVACY = <<<'TXT'
## Rukovalac
Rukovalac podacima o ličnosti je {{naziv_firme}}, {{adresa}}, {{mesto}}, PIB {{pib}}, matični broj {{maticni_broj}}. Za sva pitanja o podacima pišite na {{email_podrske}}.

## Koje podatke obrađujemo
- Podatke naloga Kupca: ime, prezime, adresu, mesto, telefon i e-poštu. Lozinku čuvamo samo u zaštićenom obliku.
- Podatke o porudžbini: artikle, cene, način dostave i plaćanja, adresu isporuke i status porudžbine.
- Podatke o prijavi: IP adresu i vreme sesije, radi bezbednosti naloga.
- Sadržaj reklamacija i poruka koje nam pošaljete.

## Svrha i pravni osnov
Osnov su odredbe člana 12. stav 1. Zakona o zaštiti podataka o ličnosti („Službeni glasnik RS”, broj 87/2018):
- izvršenje ugovora o kupovini i radnje pre zaključenja ugovora koje ste tražili (tačka 2);
- naše zakonske obaveze, na primer evidencija reklamacija i knjigovodstvo (tačka 3);
- legitimni interes zaštite naloga i sprečavanja zloupotreba (tačka 6);
- vaš pristanak, kada ga posebno tražimo (tačka 1). Pristanak možete povući u svakom trenutku.

## Ko prima podatke
Podatke dajemo samo onima koji su potrebni za izvršenje porudžbine i rad sajta: kurirskoj službi koju izaberete, pružaocu hostinga i pružaocu usluge slanja e-pošte. Podatke ne prodajemo.

## Koliko dugo čuvamo podatke
Podatke naloga čuvamo dok nalog postoji. Podatke o porudžbinama čuvamo onoliko koliko propisi o računovodstvu i zaštiti potrošača zahtevaju. Evidenciju reklamacija čuvamo najmanje dve godine (Zakon o zaštiti potrošača, član 55).

## Vaša prava
- pristup podacima i kopija podataka (član 26);
- ispravka i dopuna (član 29);
- brisanje (član 30);
- ograničenje obrade (član 31);
- prenosivost podataka (član 36);
- prigovor na obradu (član 37).

Zahtev pošaljite na {{email_podrske}}. Odgovaramo najkasnije u roku od 30 dana od prijema zahteva. Taj rok se može produžiti za još 60 dana kada je to neophodno, o čemu vas obaveštavamo u prvih 30 dana (član 21).

## Pritužba
Ako smatrate da obrada nije zakonita, možete podneti pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti (član 82) i tražiti sudsku zaštitu (član 84).

## Kolačići
Opis kolačića je na stranici „Kolačići”.
TXT;

    private const WITHDRAWAL = <<<'TXT'
## Rok
Od ugovora zaključenog na daljinu možete odustati u roku od 14 dana, bez navođenja razloga i bez dodatnih troškova, osim troškova koje zakon izričito navodi (Zakon o zaštiti potrošača, član 27).

Rok počinje da teče od dana kada roba stigne kod vas ili kod lica koje ste odredili, a koje nije prevoznik. Ako jednom porudžbinom naručite više artikala koji stižu odvojeno, rok teče od prijema poslednjeg artikla (član 28).

## Kako odustajete
Pošaljite nam izjavu o odustanku na {{email_podrske}} ili poštom na adresu {{adresa_za_reklamacije}}. Možete koristiti obrazac sa ove stranice ili bilo koju drugu jasnu izjavu. Izjava je blagovremena ako je pošaljete pre isteka roka.

## Vraćanje robe
Robu vratite bez odlaganja, a najkasnije u roku od 14 dana od dana kada ste poslali izjavu o odustanku. Rok je ispoštovan ako robu pošaljete pre njegovog isteka (član 34).

Snosite samo direktne troškove vraćanja robe. Odgovarate za umanjenu vrednost robe samo ako ste je koristili više nego što je potrebno da utvrdite njenu prirodu, svojstva i funkcionalnost.

## Povraćaj novca
Vraćamo sve uplate, uključujući troškove isporuke, bez odlaganja, a najkasnije u roku od 14 dana od dana kada primimo izjavu o odustanku. Povraćaj vršimo istim sredstvom plaćanja koje ste koristili, osim ako se izričito ne dogovorimo drugačije i bez troškova za vas. Povraćaj možemo odložiti dok ne primimo robu ili dokaz da ste je poslali (član 33).

## Kada pravo na odustanak ne postoji
Zakon isključuje ovo pravo, između ostalog, za robu izrađenu po vašim posebnim zahtevima, zapečaćenu robu koja se ne može vratiti iz higijenskih razloga ako je otpečaćena i zapečaćene audio i video zapise ili računarski softver koji su otpečaćeni posle isporuke (član 36).
TXT;

    private const COMPLAINTS = <<<'TXT'
## Naša odgovornost
Dužni smo da isporučimo robu koja je saobrazna ugovoru (Zakon o zaštiti potrošača, član 49). Odgovaramo za nesaobraznost koja se pojavi u roku od dve godine od dana kada je rizik prešao na vas. Ako se nesaobraznost pojavi u prvih šest meseci, pretpostavlja se da je postojala u trenutku prelaska rizika (član 52).

## Šta možete da tražite
Možete tražiti da nesaobraznost otklonimo bez naknade, opravkom ili zamenom, ili da tražite odgovarajuće umanjenje cene ili raskid ugovora u pogledu te robe. Između opravke i zamene birate vi. Troškove rada, materijala, preuzimanja i isporuke potrebne da roba postane saobrazna snosimo mi (član 51).

## Kako izjavljujete reklamaciju
Reklamaciju izjavljujete na adresi {{adresa_za_reklamacije}}, telefonom {{telefon}} ili e-poštom {{email_podrske}}. Uz reklamaciju dostavite račun ili drugi dokaz o kupovini. Ambalaža nije uslov za rešavanje reklamacije (član 55).

## Rokovi
1. Prijem reklamacije potvrđujemo bez odlaganja i saopštavamo broj pod kojim je zavedena.
2. Najkasnije u roku od osam dana od prijema pisano ili elektronski odgovaramo da li prihvatamo reklamaciju i predlažemo kako ćemo je rešiti.
3. Reklamaciju rešavamo najkasnije u roku od 15 dana od podnošenja, a za tehničku robu i nameštaj u roku od 30 dana.
4. Na naš predlog odgovarate u roku od tri dana. Rok za rešavanje može se produžiti samo jednom i samo uz vašu saglasnost.

Evidenciju reklamacija čuvamo najmanje dve godine (član 55).

## Garancija
Ako artikal ima garanciju, dobijate garantni list. Garancija ne isključuje i ne umanjuje vaša prava zbog nesaobraznosti robe (član 53).

## Ako odbijemo reklamaciju
Obavestićemo vas o mogućnosti vansudskog rešavanja spora i o telima koja ga sprovode. Postupak pred telom možete pokrenuti kada ste prethodno izjavili reklamaciju (član 151).
TXT;

    private const DELIVERY = <<<'TXT'
## Načini dostave
{{nacini_dostave}}

## Uslovi dostave
{{uslovi_dostave}}

## Cena dostave
{{cena_dostave}}

## Rok isporuke
Ako nije drugačije dogovoreno, isporuku vršimo u roku od 30 dana od zaključenja ugovora. Ako isporuka nije moguća, odmah vas obaveštavamo (Zakon o zaštiti potrošača, član 31).

## Načini plaćanja
{{nacini_placanja}}

Kod uplate na račun, podatke za uplatu dobijate uz porudžbinu. Porudžbinu šaljemo kada uplata bude proverena.

## Pitanja
Pišite na {{email_podrske}}.
TXT;

    private const COOKIES = <<<'TXT'
## Šta su kolačići
Kolačić je mala datoteka koju sajt čuva u vašem pregledaču.

## Koje kolačiće koristimo
Koristimo samo kolačiće neophodne za rad sajta:
- kolačić sesije, koji pamti prijavu i sadržaj korpe dok ste na sajtu;
- XSRF-TOKEN, koji štiti obrasce od lažnih zahteva;
- kolačić „Zapamti me”, samo ako ga sami uključite pri prijavi.

Ne koristimo analitičke ni marketinške kolačiće.

## Zaštita od botova
Na stranici za registraciju i prijavu može se koristiti Cloudflare Turnstile, zaštita od automatskih zloupotreba. Tu proveru sprovodi Cloudflare prema svojim pravilima privatnosti.

## Kako upravljate kolačićima
Kolačiće možete obrisati ili blokirati u podešavanjima pregledača. Bez neophodnih kolačića prijava i korpa neće raditi.

## Pitanja
Pišite na {{email_podrske}}.
TXT;
}
