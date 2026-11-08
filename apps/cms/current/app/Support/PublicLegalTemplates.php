<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Neutral Serbian default texts for the public legal pages. They are a
 * starting template, shown publicly as a draft, and the owner and a lawyer
 * must review them before the storefront opens.
 *
 * Article numbers refer to Zakon o zaštiti potrošača ("Sl. glasnik RS",
 * br. 35/2026) and Zakon o zaštiti podataka o ličnosti ("Sl. glasnik RS",
 * br. 87/2018), as read in the paragraf.rs text on 08.11.2026. A number is
 * cited only where that text was read; anything else is plain wording.
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

Članovi navedeni u zagradama odnose se na Zakon o zaštiti potrošača ("Sl. glasnik RS", br. 35/2026).

## Porudžbina
1. Artikle dodajete u korpu.
2. Porudžbinu šaljete kao prijavljeni Kupac.
3. Pre slanja vidite artikle, ukupnu cenu, način dostave i način plaćanja.
4. Slanjem porudžbine preuzimate obavezu plaćanja.

Ugovor je zaključen kada porudžbinu primimo i potvrdimo. Podatke o porudžbini dobijate na e-poštu naloga.

## Cene
Cene su u dinarima (RSD) i važe u trenutku slanja porudžbine. Cena dostave: {{cena_dostave}}.

## Plaćanje i dostava
Načini plaćanja: {{nacini_placanja}}.
Načini dostave: {{nacini_dostave}}.

Ako nije drugačije dogovoreno, robu isporučujemo bez odlaganja, a najkasnije u roku od 30 dana od zaključenja ugovora (čl. 33. i 48). Ako isporuka nije moguća, o tome vas obaveštavamo bez odlaganja (član 33).

## Pravo na odustanak
Od ugovora možete odustati u roku od 14 dana, bez navođenja razloga (član 29). Postupak i obrazac su na stranici „Pravo na odustanak od ugovora”.

## Saobraznost i reklamacije
Odgovaramo za nesaobraznost robe koja se pojavi u roku od dve godine od dana isporuke (član 59). Postupak je na stranici „Reklamacije i saobraznost robe”.

## Podaci o ličnosti
Podatke obrađujemo prema Politici privatnosti.

## Rešavanje sporova
Spor najpre pokušavamo da rešimo dogovorom. Postupak vansudskog rešavanja potrošačkog spora možete pokrenuti ako ste nam prethodno izjavili reklamaciju ili prigovor. Postupak vodi telo sa liste koju sačinjava, vodi i javno objavljuje ministarstvo nadležno za zaštitu potrošača (čl. 172. i 173).

**Po zakonu smo obavezni da učestvujemo u postupku vansudskog rešavanja potrošačkih sporova** (član 173).

## Merodavno pravo
Primenjuje se pravo Republike Srbije, a naročito Zakon o zaštiti potrošača ("Sl. glasnik RS", br. 35/2026).
TXT;

    private const PRIVACY = <<<'TXT'
## Rukovalac
Rukovalac podacima o ličnosti je {{naziv_firme}}, {{adresa}}, {{mesto}}, PIB {{pib}}, matični broj {{maticni_broj}}. Za sva pitanja o podacima pišite na {{email_podrske}}.

Članovi navedeni u ovom tekstu odnose se na Zakon o zaštiti podataka o ličnosti ("Sl. glasnik RS", br. 87/2018), osim kada je naveden drugi zakon.

## Koje podatke obrađujemo
- Podatke naloga Kupca: ime, prezime, adresu, mesto, telefon i e-poštu. Lozinku čuvamo samo u zaštićenom obliku.
- Podatke o porudžbini: artikle, cene, način dostave i plaćanja, adresu isporuke i status porudžbine.
- Podatke o prijavi: IP adresu i vreme sesije, radi bezbednosti naloga.
- Sadržaj reklamacija i poruka koje nam pošaljete.

## Svrha i pravni osnov
Osnov su odredbe člana 12. stav 1:
- izvršenje ugovora o kupovini i radnje pre zaključenja ugovora koje ste tražili (tačka 2);
- naše zakonske obaveze, na primer evidencija reklamacija i knjigovodstvo (tačka 3);
- legitimni interes zaštite naloga i sprečavanja zloupotreba (tačka 6);
- vaš pristanak, kada ga posebno tražimo (tačka 1). Pristanak možete povući u svakom trenutku.

## Ko prima podatke
Podatke dajemo samo onima koji su potrebni za izvršenje porudžbine i rad sajta: kurirskoj službi koju izaberete, pružaocu hostinga, pružaocu usluge slanja e-pošte i pružaocu zaštite od automatskih zloupotreba. Podatke ne prodajemo.

## Prenos podataka u druge države
Neki pružaoci usluga mogu obrađivati podatke van Republike Srbije, ako se njihovi serveri ili sedište nalaze u drugoj državi. To se može odnositi na pružaoca hostinga, pružaoca usluge slanja e-pošte i Cloudflare (zaštita od botova Turnstile i isporuka sadržaja).

Pružaoci, države i osnov prenosa: [trgovac upisuje pružaoce, države u kojima obrađuju podatke i osnov prenosa].

Podatke u drugu državu prenosimo samo pod uslovima iz čl. 63. do 65.

## Koliko dugo čuvamo podatke
Podatke naloga čuvamo dok nalog postoji. Podatke o porudžbinama čuvamo onoliko koliko propisi o računovodstvu i zaštiti potrošača zahtevaju. Evidenciju reklamacija čuvamo najmanje dve godine od dana podnošenja reklamacije (Zakon o zaštiti potrošača, "Sl. glasnik RS", br. 35/2026, član 63).

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
Članovi navedeni u zagradama odnose se na Zakon o zaštiti potrošača ("Sl. glasnik RS", br. 35/2026).

## Rok
Od ugovora zaključenog na daljinu možete odustati u roku od 14 dana, bez navođenja razloga i bez dodatnih troškova (član 29). Izuzetak su direktni troškovi vraćanja robe i, ako ste izričito tražili skuplju dostavu od najjeftinije uobičajene koju nudimo, razlika u ceni te dostave (čl. 35. i 36).

Rok počinje da teče od dana kada roba stigne kod vas ili kod lica koje ste odredili, a koje nije prevoznik. Ako jednom porudžbinom naručite više vrsta robe koje stižu odvojeno, rok teče od dana kada stigne poslednja. Ako roba stiže u više pošiljki ili delova, rok teče od prijema poslednje pošiljke ili dela (član 30).

## Kako odustajete
Pošaljite nam izjavu o odustanku na {{email_podrske}} ili poštom na adresu {{adresa_za_reklamacije}}. Možete koristiti obrazac sa ove stranice ili bilo koju drugu nedvosmislenu izjavu. Izjava je blagovremena ako je pošaljete pre isteka roka i proizvodi dejstvo od dana kada je poslata (član 29).

Obrazac za odustanak i obaveštenje o ovom pravu dužni smo da vam predamo i na trajnom nosaču podataka, na primer e-poštom, najkasnije u vreme isporuke robe (član 32).

## Vraćanje robe
Robu vratite bez odlaganja, a najkasnije u roku od 14 dana od dana kada ste poslali izjavu o odustanku. Rok je ispoštovan ako robu pošaljete pre njegovog isteka (član 36).

Snosite samo direktne troškove vraćanja robe, osim ako se nismo saglasili da ih snosimo mi (član 36). Odgovarate za umanjenu vrednost robe samo ako ste njome rukovali više nego što je potrebno da utvrdite njenu prirodu, karakteristike i funkcionalnost (član 36).

## Povraćaj novca
Vraćamo sve što ste platili na osnovu ugovora, uključujući troškove isporuke, bez odlaganja, a najkasnije u roku od 14 dana od dana kada primimo vašu izjavu o odustanku. Povraćaj vršimo istim sredstvom plaćanja koje ste koristili, osim ako se izričito ne saglasite sa drugim sredstvom, i bez troškova za vas. Povraćaj možemo odložiti dok ne primimo robu ili dokaz da ste je poslali, šta god nastupi prvo (član 35).

## Kada pravo na odustanak ne postoji
Zakon isključuje ovo pravo, između ostalog, za robu proizvedenu prema vašim posebnim zahtevima ili jasno personalizovanu, za zapečaćenu robu koja se ne može vratiti zbog zaštite zdravlja ili higijenskih razloga ako je otpečaćena posle isporuke i za zapečaćene audio i video zapise ili računarski softver koji su otpečaćeni posle isporuke (član 38).
TXT;

    private const COMPLAINTS = <<<'TXT'
Članovi navedeni u zagradama odnose se na Zakon o zaštiti potrošača ("Sl. glasnik RS", br. 35/2026).

## Naša odgovornost
Dužni smo da isporučimo robu koja je saobrazna ugovoru (član 53). Odgovaramo za nesaobraznost koja se pojavi u roku od dve godine od dana isporuke. Ako se nesaobraznost pojavi u roku od godinu dana od isporuke, pretpostavlja se da je postojala u trenutku isporuke (član 59).

O nesaobraznosti nas obavestite u roku od dva meseca od kada ste je uočili, a najkasnije u roku od dve godine od isporuke (član 59).

## Šta možete da tražite
1. Najpre birate između opravke i zamene. Otklanjanje nesaobraznosti je za vas besplatno. Troškove rada, materijala, preuzimanja i isporuke zamenjene robe snosimo mi (član 56).
2. Opravku ili zamenu možemo odbiti samo ako nisu moguće ili bi za nas bile nesrazmerno opterećenje (član 56).
3. Umanjenje cene ili raskid ugovora možete tražiti ako opravku ili zamenu nismo dovršili, nismo otklonili nesaobraznost ili smo to odbili, ako nesaobraznost i posle pokušaja nije otklonjena, ako je jasno da je nećemo otkloniti u razumnom roku ili bez značajnih neugodnosti za vas, ili ako je nesaobraznost toliko znatna da to opravdava (član 57).
4. Ako se nesaobraznost pojavi u roku od 30 dana od isporuke, možete odmah birati između zamene, umanjenja cene i raskida ugovora (član 56).

Ako se posle prve opravke pojavi isti ili drugi nedostatak, možete tražiti zamenu, umanjenje cene ili raskid ugovora. Ponovna opravka moguća je samo uz vaš izričit pristanak (član 56). Ugovor ne možete raskinuti ako je nesaobraznost neznatna (član 56).

Ako raskinete ugovor, robu vraćate o našem trošku, a cenu vraćamo u roku od tri dana od kada primimo robu ili dokaz da ste je poslali (član 57).

## Kako izjavljujete reklamaciju
Reklamaciju izjavljujete usmeno na adresi {{adresa_za_reklamacije}}, telefonom {{telefon}}, pisanim putem ili e-poštom {{email_podrske}}. Uz reklamaciju dostavite na uvid račun ili drugi dokaz o kupovini. Utvrđivanje nesaobraznosti ne naplaćujemo. Ambalaža nije uslov za rešavanje reklamacije (član 63).

## Rokovi
1. Prijem reklamacije potvrđujemo bez odlaganja, pisano ili elektronski, i saopštavamo broj pod kojim je zavedena.
2. Najkasnije u roku od osam dana od prijema odgovaramo pisano ili elektronski da li prihvatamo reklamaciju i predlažemo kako i u kom roku ćemo je rešiti.
3. Reklamaciju rešavamo najkasnije u roku od 15 dana od podnošenja, a za tehničku robu i nameštaj u roku od 30 dana.
4. Na naš predlog odgovarate najkasnije u roku od tri dana od prijema. Rok za rešavanje može se produžiti samo jednom i samo uz vašu saglasnost.

Evidenciju reklamacija čuvamo najmanje dve godine od dana podnošenja reklamacije (član 63).

## Komercijalna garancija
Ako artikal ima komercijalnu garanciju, garantni list dobijate pisano, elektronski ili na drugom trajnom nosaču podataka najkasnije pri isporuci. Garancija ne isključuje i ne umanjuje vaša prava zbog nesaobraznosti robe (član 61).

## Ako odbijemo reklamaciju
Obavestićemo vas o mogućnosti vansudskog rešavanja spora i o telima koja ga sprovode (član 63). Postupak pred telom možete pokrenuti ako ste nam prethodno izjavili reklamaciju. Po zakonu smo obavezni da učestvujemo u tom postupku (član 173).
TXT;

    private const DELIVERY = <<<'TXT'
## Načini dostave
{{nacini_dostave}}

## Uslovi dostave
{{uslovi_dostave}}

## Cena dostave
{{cena_dostave}}

## Rok isporuke
Ako nije drugačije dogovoreno, robu isporučujemo bez odlaganja, a najkasnije u roku od 30 dana od zaključenja ugovora. Ako isporuka nije moguća, o tome vas obaveštavamo bez odlaganja. Ostavljanje robe ispred vrata ne smatra se urednom isporukom (Zakon o zaštiti potrošača, "Sl. glasnik RS", br. 35/2026, čl. 33. i 48).

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
