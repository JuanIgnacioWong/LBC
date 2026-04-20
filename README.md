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

- `develop` -> staging
- `main` -> produccion

Workflow:

- `.github/workflows/deploy-cpanel.yml`
