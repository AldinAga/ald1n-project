{{-- Draft notice for legal texts not yet approved by the merchant. Author: Ald1n <https://ald1n.com> --}}
@if($draft ?? true)
<div class="legal-draft" role="note" aria-labelledby="legal-draft-title" data-legal-draft>
<strong id="legal-draft-title">Nacrt — tekst je na pregledu kod trgovca</strong>
<p>Ovaj tekst još nije konačan i trgovac ga nije potvrdio. Može se promeniti pre početka prodaje.</p>
</div>
@endif
