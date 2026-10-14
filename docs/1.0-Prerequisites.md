## Prerequisites

| Tool                                                          | Version           | Notes                                                       |
| ------------------------------------------------------------- | ----------------- | ----------------------------------------------------------- |
| [Docker](https://docs.docker.com/get-docker/) with Compose v2 | Recent            | Docker Desktop, OrbStack, Colima or Docker Engine all work. |
| [Node.js](https://nodejs.org/)                                | 24 (see `.nvmrc`) | `nvm use` picks up the right version.                       |
| Git                                                           | Any               | Commit hooks are installed by `npm install`.                |

**PHP and Composer are optional on your machine.** Every PHP command (`npm run composer`, `npm run lint:php`, `npm run analyse`, `npm run test:php`) uses your local PHP if it is 8.3+ with the `dom`, `mbstring`, `tokenizer` and `xmlwriter` extensions; otherwise it runs inside a `composer:2` container. Set `WHALEPRESS_PHP=container` to always use the container.

### Ports

The defaults are below; change any of them in `.env` if they clash with something else.

| Service         | Default |
| --------------- | ------- |
| WordPress       | 8088    |
| Adminer         | 8080    |
| Mailpit         | 8025    |
| Vite dev server | 5173    |

### Editor

VS Code users get extension recommendations from `.vscode/extensions.json`, and can copy the debugging and formatting settings from `local/.vscode-example/`. A [devcontainer](./1.3-Local-Services.md#devcontainer) is also available.
