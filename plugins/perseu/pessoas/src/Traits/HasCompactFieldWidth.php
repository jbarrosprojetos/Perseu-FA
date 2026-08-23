<?php

namespace Perseu\Pessoas\Traits;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Flex;

/**
 * Reusable rule for form field visual width: short/known-format fields
 * (phone, CPF/CNPJ, RG, CEP, dates, selects) should not stretch to fill
 * their grid column on wide screens. Width is derived from the number of
 * characters the field's content is expected to have, in the "ch" CSS
 * unit (the width of the "0" character in the current font), plus a
 * small slack margin for input padding and, where relevant, icons.
 * Wrap the component with static::compact($component, chars: N) instead
 * of a fixed pixel value.
 *
 * When a row mixes compact fields with a "normal width" field (the one
 * that should absorb whatever space is left), use static::flexRow() +
 * static::grow() instead of Grid::make(N) — an equal-width Grid column
 * leaves empty space around a compact field instead of letting it sit
 * flush against its neighbours.
 */
trait HasCompactFieldWidth
{
    protected static function compactFieldSlack(): int
    {
        return 2;
    }

    /**
     * Extra slack (on top of compactFieldSlack()) for `Select` — unlike a
     * plain `TextInput`, a non-native Select (any Select with
     * ->searchable() and/or ->multiple(), which is effectively every
     * Select in this project — see HasOptions/CanBeSearchable in
     * Filament\Forms\Components\Select::isNative()) renders through the
     * JS "choices" component instead of a bare `<select>`, which draws a
     * clear ("X") button AND a dropdown chevron INSIDE the same
     * `.fi-input-wrp` box the value text sits in. Neither icon is part of
     * the character count, so without this the selected option's text
     * visually collides with/hides behind them — reported with
     * `tipo_projeto_id`/`contato_pessoa_fisica_id`/`pessoa_fisica_id`/
     * `pessoa_juridica_id` in Comercial's ProjetoResource and confirmed
     * also affecting `estado_civil`/`sexo` in Pessoas' PessoaFisicaResource
     * (same compact() call, same missing slack — not something specific
     * to Comercial).
     */
    protected static function selectIconSlack(): int
    {
        return 6;
    }

    protected static function extraSlackFor(mixed $component): int
    {
        return $component instanceof Select ? static::selectIconSlack() : 0;
    }

    /**
     * Also works for `Placeholder`/`TextEntry` (no `.fi-input-wrp`, since
     * it's not really an input) — ->extraAttributes() lands on its own
     * root wrapper in that case, which is exactly the box that needs the
     * max-width. Confirmed via HTML render (Comercial's ProjetoResource
     * uses this for read-only Email/Telefone Placeholders next to a
     * Select, sized by the expected VALUE, not just the label — see
     * compactByLabel() for the case where only the label length matters).
     */
    protected static function compact(mixed $component, int $chars, int $extraSlack = 0): mixed
    {
        // The label sits on its own line above the input but is part of the
        // same flex item (.fi-fo-field) and doesn't wrap (nowrap), so if it's
        // longer than the value's expected width, it visually spills into
        // the next field in the row even though the input box itself has
        // room. Sizing the field to whichever is wider — value or label —
        // removes the mismatch instead of just letting the label overflow.
        $chars = max($chars, static::labelChars($component));

        $width = $chars + static::compactFieldSlack() + $extraSlack + static::extraSlackFor($component);

        // Filament wraps TextInput/Select/DatePicker's <input>/<select> in an
        // outer ".fi-input-wrp" div, which is the element that actually
        // carries the visible border/background/ring (the input itself has
        // border-none/bg-transparent). That wrapper is a flex container with
        // no width of its own — it stretches to fill the grid column
        // regardless of the inner input's width. ->extraAttributes() (not
        // ->extraInputAttributes()) is what lands on that wrapper div
        // (confirmed via Field::wrapInputHtml() in filament/forms), so the
        // max-width has to be set there for the visible box itself to
        // shrink — setting it only on the inner input leaves empty space
        // inside the border.
        //
        // ->grow(false): inside a static::flexRow(), Filament's Flex marks
        // every child "fi-growable" (flex-1) by default — Flex::toEmbeddedHtml()
        // calls $schemaComponent->canGrow() with no $default argument, and
        // CanGrow::canGrow(bool $default = true) defaults to true when grow()
        // was never called. A compact field must opt out explicitly or it
        // stretches and the max-width above only limits its content, not the
        // flex slot it sits in.
        return $component
            ->grow(false)
            ->extraAttributes([
                'style' => 'max-width: '.$width.'ch;',
            ]);
    }

    protected static function labelChars(mixed $component): int
    {
        $label = $component->getLabel();

        return is_string($label) ? mb_strlen($label) : 0;
    }

    /**
     * Same leak fix as static::compact(), for fields that have no
     * ".fi-input-wrp" of their own to size by value (e.g. Toggle, which
     * renders a fixed-size <button> and gets its width from the label
     * alone). Constrains the field wrapper (.fi-fo-field, the flex item
     * itself) directly via ->extraFieldWrapperAttributes(), since there's
     * no input box whose max-width would otherwise carry it.
     *
     * `Filament\Forms\Components\Placeholder` (deprecated, extends
     * `Infolists\Components\TextEntry`) doesn't have
     * `extraFieldWrapperAttributes()` at all — that method comes from a
     * Field-schema concern the Entry class hierarchy doesn't use. For
     * that case, ->extraAttributes() lands on the entry's own root
     * wrapper (there's no separate ".fi-fo-field" to target), which
     * constrains the same visual box since Placeholder has no
     * ".fi-input-wrp" nesting to worry about either.
     */
    protected static function compactByLabel(mixed $component, int $extraSlack = 0): mixed
    {
        $width = static::labelChars($component) + static::compactFieldSlack() + $extraSlack + static::extraSlackFor($component);

        $component->grow(false);

        return method_exists($component, 'extraFieldWrapperAttributes')
            ? $component->extraFieldWrapperAttributes(['style' => 'max-width: '.$width.'ch;'])
            : $component->extraAttributes(['style' => 'max-width: '.$width.'ch;']);
    }

    protected static function flexRowGap(): string
    {
        return '2ch';
    }

    protected static function flexRowFieldMargin(): string
    {
        return '2ch';
    }

    /**
     * Lays a row of fields out with Filament's own `Flex` schema
     * component instead of a Grid: compact fields (wrapped with
     * static::compact()) size to their own max-width and sit flush next
     * to each other with a small gap, and any field wrapped with
     * static::grow() stretches to fill whatever space is left. An
     * equal-width Grid column leaves dead space around a compact field,
     * which is exactly what this avoids.
     *
     * A hand-rolled Group::make()->extraAttributes(['class' => 'flex'])
     * does NOT work for this: Schema::toHtml() always wraps a
     * component's children in its own CSS Grid div (.fi-grid, 1 column
     * by default) before they ever reach the parent's flex container, so
     * the fields still stack instead of sitting side by side. Flex
     * renders its children directly (no inner grid wrapper) and already
     * ships the compact-gap (->dense()) and grow (->grow(), "fi-growable"
     * => flex-1) mechanics used here.
     *
     * ->dense() alone isn't enough, though: HasGap::gap() is a boolean
     * has-gap/no-gap toggle, not a numeric setter, so ->dense() can only
     * flip between Flex's two hardcoded classes — "fi-sc-flex" (gap-6,
     * 1.5rem) and "fi-sc-flex fi-dense" (gap-3, 0.75rem). Neither leaves
     * enough room for a field's label not to visually run into its
     * neighbour's, so the gap is pinned to an explicit value (see
     * flexRowGap()) via ->extraAttributes(['style' => 'gap: ...']).
     *
     * That inline style needs its own !important: the qalainau/bonsai-theme
     * package installed in this project ships `.fi-sc-flex, .fi-sc-flex.fi-dense
     * { gap: 0 !important; }` (its whole design is zero-gap, high-density
     * forms), and an author stylesheet's !important always wins over a
     * non-important inline style regardless of specificity — a plain
     * inline `gap: 2ch` here was silently overridden back to 0 the entire
     * time this trait existed, with no visible effect. Confirmed by
     * reading vendor/qalainau/bonsai-theme/resources/css/bonsai.css.
     * ->extraAttributes()'s style value is used verbatim, so `!important`
     * in the string is all that's needed — no Filament API for it.
     *
     * On top of the container gap, every field also gets its own
     * margin-right (static::flexRowFieldMargin()) on its outermost
     * wrapper (.fi-fo-field, the flex item itself, via
     * ->extraFieldWrapperAttributes(merge: true) so it doesn't clobber
     * the max-width style static::compactByLabel() may have already put
     * there). The container gap sits BETWEEN the flex items' boxes, but
     * a field's box can be exactly as wide as its content (see
     * static::compact()'s label-vs-value width fix) with no breathing
     * room of its own — this margin is a per-item reinforcement of that
     * spacing, not a replacement for the gap.
     *
     * @param  array<mixed>  $components
     */
    protected static function flexRow(array $components): Flex
    {
        foreach ($components as $component) {
            $style = ['style' => 'margin-right: '.static::flexRowFieldMargin().';'];

            // Placeholder (deprecated Forms component, extends the Infolists
            // TextEntry hierarchy) has no ".fi-fo-field" wrapper concern, so
            // it falls back to the component's own ->extraAttributes() —
            // same target compactByLabel() already uses for it, and Laravel's
            // ComponentAttributeBag concatenates repeated `style` merges
            // instead of overwriting, so both end up on the same element.
            method_exists($component, 'extraFieldWrapperAttributes')
                ? $component->extraFieldWrapperAttributes($style, merge: true)
                : $component->extraAttributes($style, merge: true);
        }

        return Flex::make($components)
            ->dense()
            ->extraAttributes([
                'style' => 'gap: '.static::flexRowGap().' !important;',
            ]);
    }

    /**
     * Marks a field to absorb the remaining space in a static::flexRow()
     * — the counterpart to static::compact() for that row's one
     * "normal width" field. Thin wrapper around Filament's own
     * Component::grow(), kept here so the pairing with flexRow()/
     * compact() is obvious at the call site.
     */
    protected static function grow(mixed $component): mixed
    {
        return $component->grow();
    }

    /**
     * Longest getLabel() among a HasLabel backed enum's cases, in
     * characters (mb_strlen, so it stays correct for accented labels).
     * Lets compact() width follow the enum automatically instead of a
     * value that needs to be updated by hand if a case changes.
     *
     * @param  class-string  $enumClass
     */
    protected static function maxEnumLabelChars(string $enumClass): int
    {
        return collect($enumClass::cases())
            ->map(fn ($case) => mb_strlen($case->getLabel()))
            ->max();
    }
}
