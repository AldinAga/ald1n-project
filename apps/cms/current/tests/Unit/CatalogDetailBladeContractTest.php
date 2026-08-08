<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CatalogDetailBladeContractTest extends TestCase
{
    public function test_variant_markup_does_not_use_ambiguous_inline_blade_chains(): void
    {
        $view = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/catalog/show.blade.php');

        self::assertStringContainsString('$variantImage = $variant->images->first()?->url;', $view);
        self::assertStringNotContainsString('@foreach($variant->specificationValues as $value)@php', $view);
        self::assertStringNotContainsString("@can('orders.create')@if", $view);
        self::assertStringNotContainsString('@if($variant->specificationValues->isNotEmpty())<ul', $view);
        self::assertSame(substr_count($view, '@foreach'), substr_count($view, '@endforeach'));
        self::assertSame(substr_count($view, '@if'), substr_count($view, '@endif'));
        self::assertSame(substr_count($view, '@can'), substr_count($view, '@endcan'));
    }
}
