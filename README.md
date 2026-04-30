# bunny-magic-containers

Small **PHP + Apache** demo (Home, About, Contact) packaged for **[Bunny Magic Containers](https://docs.bunny.net/magic-containers)**. The app listens on **port 80** inside the container.

## Run locally

Requires [Docker](https://docs.docker.com/get-docker/) (Docker Desktop or the CLI).

```bash
docker compose up --build
```

Open **http://localhost:8080/**.

## Deploy on bunny.net

Point Magic Containers at **GitHub Container Registry (GHCR)** — for example **Registry: GitHub Public** and image **`your-user/bunny-magic-containers`** with tag **`latest`** or a commit SHA. Endpoint **container port: 80**.

That UI reads from the **image in GHCR**, not from raw Git commits. To refresh the site after you change code, a **new image** must be built and pushed (this repo does that with **GitHub Actions** below).

### GitHub Actions (build, push, optional Bunny update)

On every push to **`main`**, [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml):

1. Builds **`linux/amd64`** and pushes to GHCR as **`:latest`** and **`:<commit-sha>`**.
2. If the repo variable **`APP_ID`** is set, calls Bunny’s **[container-update-image](https://docs.bunny.net/magic-containers/deploy-with-github-actions)** action so Magic Containers rolls to the new **SHA** tag (same flow as [Update App](https://docs.bunny.net/magic-containers/update), but automated).

**One-time GitHub setup**

1. **Packages:** Repository **Settings → Actions → General → Workflow permissions** — enable **Read and write** so `GITHUB_TOKEN` can push to GHCR (or use a PAT; see [GitHub Packages docs](https://docs.github.com/en/packages/learn-github-packages/publishing-a-package)).
2. **Bunny API key:** **Settings → Secrets and variables → Actions → Secrets** — add **`BUNNYNET_API_KEY`** (your bunny.net account API key; sub-users may be unsupported — see Bunny’s action docs).
3. **App ID:** **Settings → Secrets and variables → Actions → Variables** — add **`APP_ID`** with your Magic Containers app id from the Bunny dashboard. If **`APP_ID`** is empty, the workflow still builds and pushes to GHCR, but skips the Bunny update step.
4. **Container name (optional):** Variable **`BUNNY_CONTAINER`** — name of the container inside the app (default in the workflow is **`Container-1`**). Set this if yours differs.

**Bunny dashboard**

- Image should match GHCR, e.g. **`dashpilot/bunny-magic-containers`** with tag **`latest`** (Actions overwrites `latest` each run) **or** rely on the action to set the deployment to the **SHA** tag when `APP_ID` is configured.

After a successful workflow, Bunny may show a banner like **“New version … tag: latest … Apply Update”** for **Container-1**. That means GHCR has a new digest for `latest`; click **Apply Update** to pull and redeploy. If you configure **`APP_ID`** and the **`container-update-image`** step runs successfully, Bunny can roll to the **commit SHA** tag automatically; you might still see dashboard prompts depending on how the app is wired—either way, **Apply Update** applies the new image you just pushed.

### Private repositories

GHCR and Bunny may need extra auth for private packages; see the [Magic Containers deploy guide](https://docs.bunny.net/docs/magic-containers-how-to-deploy-your-app).

## Project layout

| Path | Purpose |
|------|--------|
| `Dockerfile` | `php:8.3-apache`, copies `public/` into the web root, exposes **80** |
| `public/` | Front controller `index.php`, `.htaccess` (pretty URLs → `index.php`), `includes/`, `styles.css` |
| `docker/apache-allow-htaccess.conf` | Enables `AllowOverride All` so Apache reads `.htaccess` |
| `docker-compose.yml` | Local dev: map host **8080** → container **80**, `linux/amd64` |
| `.github/workflows/deploy.yml` | CI: GHCR push + optional Bunny rolling update |

## Notes

- **Architecture:** Magic Containers expects **linux/amd64** images. `docker-compose.yml` sets `platform: linux/amd64` so local builds match production on Apple Silicon.
- **No database** — the contact form is a demo only; nothing is stored.
