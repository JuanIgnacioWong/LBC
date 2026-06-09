# LBC - Entorno Local y Deploy

Proyecto WordPress para la Liga de Basquetbol Chile.

Guia completa y detallada:

- `docs/PROCESO_LOCAL_DEPLOY_CPANEL.md`

## Inicio rapido local

```bash
ddev start
ddev wp-bootstrap
ddev wp-seed-demo
```

URLs:

- `https://lbc.ddev.site`
- `http://127.0.0.1:8085`

## Deploy CI/CD

WordPress ya debe estar instalado en el hosting. El deploy sube solo el tema:

- origen: `wp-content/themes/liga-basket-chile/`
- destino produccion: `public_html/wp-content/themes/liga-basket-chile/`

Opciones disponibles:

- cPanel Git Version Control: usa `.cpanel.yml`
- GitHub Actions por SSH: usa `.github/workflows/deploy-cpanel.yml`

Ramas:

- `develop` -> staging, si existe un subdominio de staging configurado
- `main` -> produccion en `lbcchile.com`

Workflow:

- `.github/workflows/deploy-cpanel.yml`
