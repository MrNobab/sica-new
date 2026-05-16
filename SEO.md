# SEO — SICA Creative

> Ficheiro de referência para todas as decisões de SEO do projeto.
> Usar em conjunto com o CLAUDE.md para contexto de marca.

---

## Domínio de produção
`https://sicacreative.com`

## Estado atual
🔴 Site em desenvolvimento — indexação bloqueada via `robots.txt` e `noindex`

---

## Páginas

### /pt/ — Homepage
- **Title:** `Agência de Marketing Digital em Lisboa | SICA Creative`
- **Meta description:** `Web design, SEO, Google Ads e Meta Ads para pequenas e médias empresas. Transformamos o teu digital num canal de crescimento previsível.`
- **Keywords alvo:** agência marketing digital lisboa, google ads pme, web design lisboa
- **Status:** ⬜ Por fazer

---

### /pt/servicos/ — Serviços
- **Title:** `Serviços de Marketing Digital | SICA Creative`
- **Meta description:** `Web design, SEO, gestão de redes sociais, Google Ads e Meta Ads. Descobre como podemos ajudar o teu negócio a crescer online.`
- **Keywords alvo:** serviços marketing digital, google ads lisboa, meta ads portugal
- **Status:** ⬜ Por fazer

---

### /pt/sobre/ — Sobre
- **Title:** `Sobre a SICA Creative | Agência Digital em Lisboa`
- **Meta description:** `Somos uma agência de marketing digital baseada em Lisboa, focada em resultados para PMEs portuguesas.`
- **Keywords alvo:** agência digital lisboa, sobre sica creative
- **Status:** ⬜ Por fazer

---

### /pt/contacto/ — Contacto
- **Title:** `Contacto | SICA Creative`
- **Meta description:** `Fala connosco. Estamos em Lisboa e trabalhamos com negócios em todo o país.`
- **Keywords alvo:** contacto agência marketing digital
- **Status:** ⬜ Por fazer

---

## Notas técnicas

- [ ] `robots.txt` com `Disallow: /` (desenvolvimento)
- [ ] `noindex` no `<head>` de todas as páginas (desenvolvimento)
- [ ] Trocar para indexação normal no lançamento
- [ ] Submeter sitemap no Google Search Console após lançamento
- [ ] Imagem OG (`/assets/og-image.jpg`) — 1200×630px

---

## Como usar com o Claude Code

Para aplicar as meta tags de uma página:
```
Update the <head> of /pt/index.html with the SEO data from SEO.md for the homepage.
Add title, meta description, canonical, hreflang pt/en, and Open Graph tags.
```

---

*Atualizar este ficheiro sempre que adicionar páginas novas ao projeto.*
