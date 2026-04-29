# bunny-magic-containers

Small **PHP + Apache** demo (Home, About, Contact) packaged for **[Bunny Magic Containers](https://docs.bunny.net/magic-containers)**. The app listens on **port 80** inside the container.

## Run locally

Requires [Docker](https://docs.docker.com/get-docker/) (Docker Desktop or the CLI).

```bash
docker compose up --build
```

Open **http://localhost:8080/**.

## Deploy on bunny.net (recommended: GitHub repo)

If your repository is **public**, Bunny can **build from your repo** and deploy without a separate container registry or GitHub Actions.

1. In the [bunny.net](https://bunny.net) dashboard, open **Magic Containers** and create an app (or use **Deploy** / the flow that connects GitHub).
2. Choose the option to deploy from a **GitHub** repository and authorize access if asked.
3. Select this repo and branch (usually `main`).
4. Ensure the **container port** for the HTTP endpoint is **80** (matches this `Dockerfile`).

Bunny builds the image from the **`Dockerfile` at the repository root** and rolls out your app. Official overview: [Magic Containers documentation](https://docs.bunny.net/magic-containers).

### Private repositories

If the repo is private, you typically need either **registry-based deploy** (build and push an image to GHCR or Docker Hub, then point Magic Containers at that image) or whatever private-Git integration Bunny documents at the time. Check the current [deploy guide](https://docs.bunny.net/docs/magic-containers-how-to-deploy-your-app) for options.

## Project layout

| Path | Purpose |
|------|--------|
| `Dockerfile` | `php:8.3-apache`, copies `public/` into the web root, exposes **80** |
| `public/` | Site: `index.php`, `about.php`, `contact.php`, shared layout and CSS |
| `docker-compose.yml` | Local dev: map host **8080** → container **80**, `linux/amd64` |

## Notes

- **Architecture:** Magic Containers expects **linux/amd64** images. `docker-compose.yml` sets `platform: linux/amd64` so local builds match production on Apple Silicon.
- **No database** — the contact form is a demo only; nothing is stored.
