# Logo v Blade šablonách

Direktiva vykreslí SVG ze složky `public/assets/logo`:

```blade
@logo
@logo('auto')
@logo('light')
@logo('dark')
@logo('mono-white')
@logo('mono-black')
@logo('symbol-light')
@logo('symbol-dark')
```

`light` je barevné logo s černým textem na světlé pozadí, `dark` má bílý text na
tmavé pozadí. `mono-white` a `mono-black` jsou jednobarevné varianty.

Automatický režim respektuje nejbližší nadřazený prvek s `data-bs-theme="light"`
nebo `data-bs-theme="dark"`. Pokud žádný není, použije systémové nastavení
`prefers-color-scheme`. Změny se projeví ihned přes CSS, bez obnovení stránky.
Konkrétní zadaná varianta se podle vzhledu nepřepíná.

Barvu libovolného pozadí direktiva neodhaduje. Pro plochu s pevným pozadím určete
její vzhled nebo vyberte konkrétní variantu:

```blade
<div class="bg-dark" data-bs-theme="dark">
    @logo
</div>

@logo('mono-white', ['class' => 'my-3', 'width' => 150, 'alt' => 'NoQ – úvod'])
@logo($variant)
```

Druhý parametr přijímá HTML atributy. `alt`, `width` a `height` patří obrázku,
ostatní atributy (například `class`, `id`, `style`) obalovému prvku `<span>`.
Výchozí šířka je 180 px, výška se přes CSS přizpůsobuje poměru stran SVG.
Neznámá varianta vyvolá výjimku místo nefunkčního odkazu na soubor.

Šablona musí načítat `resources/scss/app.scss` přes `@vite`, stejně jako stávající
layouty projektu. Po změně SCSS spusťte `npm run build` nebo `npm run dev`.
