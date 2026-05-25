<div align="center">
    <h1>GEWISWEB - The GEWIS Website</h1>

<!-- Shield group -->
[![Latest Release](https://img.shields.io/github/v/release/GEWIS/gewisweb)](https://github.com/GEWIS/gewisweb/releases)
[![Build](https://img.shields.io/github/check-runs/GEWIS/gewisweb/main)](https://github.com/GEWIS/gewisweb/actions)
[![Uptime](https://uptime.gewis.nl/api/badge/1/uptime)](https://gewis.nl/en/)
[![Issues](https://img.shields.io/github/issues/GEWIS/gewisweb)](https://github.com/GEWIS/gewisweb/issues)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/GEWIS/gewisweb/main)](https://github.com/GEWIS/gewisweb/commits/main)
[![License](https://img.shields.io/github/license/GEWIS/gewisweb.svg)](./LICENSE.txt)

<p>GEWISWEB is the <a href="https://gewis.nl" target="_blank">website</a> created by and for the members of GEWIS - <em>GEmeenschap van Wiskunde en Informatica Studenten</em>.</p>
</div>

## Features
The GEWIS website provides its members and other visitors with lots of functionality:

- **Activities**:
    - Create activities with a wide range of options for sign-up lists.
    - Enables members to sign up for various events and activities, enhancing engagement and participation.

- **Career**:
    - Allows companies that collaborate with GEWIS to publish job vacancies and opportunities.
    - Facilitates connections between students and potential employers, aiding in career development.

- **Decisions**:
    - Provides a platform for members to view and interact with decisions and meetings.
    - Ensures transparency and member involvement in the decision-making process.

- **Education**:
    - Offers an extensive archive of course documents, including exams and summaries.
    - Serves as a valuable resource for students looking to study or review past materials.

- **Pages**:
    - Custom pages created by the board to provide dynamic content.
    - Allows for flexible and timely updates to information and announcements.

- **Photos**:
    - Maintains a comprehensive photo archive of the numerous activities organised by GEWIS.
    - Helps preserve and share memories of events and gatherings with the community.

And there is plenty more! GEWISWEB continuously evolves to meet the needs of the association's members, offering a broad array of tools and features to enrich their time at the university.

## Getting Started
GEWISWEB is built on PHP and the [Symfony framework](https://symfony.com/). The Symfony framework provides a solid foundation for building scalable and maintainable web applications.

### Prerequisites
We recommend developing natively on a Linux machine or through WSL2 on Windows with the [PhpStorm](https://www.jetbrains.com/phpstorm/) IDE or another IDE with good support for PHP.

You will need at least:
- `docker` and `docker compose` (make sure that you have enabled [Buildkit](https://docs.docker.com/build/buildkit/#getting-started))
- `git`
- `make`
- A `.xlf` file editor (e.g. POEdit)

PHP, Composer, and all other runtime tooling live inside the Docker image, no need to install them yourself.

### Installation
To set up GEWISWEB locally, follow these steps:

1. [Fork the repository](https://github.com/GEWIS/gewisweb/fork).
2. Clone your fork (`git clone git@github.com:{username}/gewisweb.git`).
3. Initialise submodules (`git submodule update --init`). This pulls the read-only `gewisweb-laminas/` reference of the pre-migration codebase.
4. Copy the `.env.local.dist` file to `.env.local` and alter the file to your needs.
5. Run `make start` to build and serve the website.
6. Run `make seed` to get some test data (migrations will run automatically).
7. Go to [`http://localhost/`](http://localhost/) in your browser and you are greeted with the GEWIS website.
8. Log in with membership number `8000` and the password `gewiswebgewis`.

#### Other Accessible Services
During development, several other services are accessible on your local machine:

- **phpMyAdmin** - Database management interface at [`http://localhost:8080/`](http://localhost:8080/).
- **MailPit** - Email testing at [`http://localhost:8025/`](http://localhost:8025/).
- **RabbitMQ** - Message broker management at [`http://localhost:15672/`](http://localhost:15672/).
- **Matomo** - Analytics platform at [`http://localhost:82/`](http://localhost:82/).

### Contributing
We welcome contributions from the community, especially GEWIS members! To contribute:

1. Perform the steps from [Installation](#installation).
2. Create your feature of bug fix branch (`git switch -c feature/my-amazing-feature`).
3. Commit your changes (`git commit -m 'feat: added my amazing feature'`).
4. Push to the branch (`git push origin feature/my-amazing-feature`).
5. Open a pull request.

> [!NOTE]
> More detailed information on GEWIS' contribution guidelines, including conventions on branch names and commit messages, can be found in the [contribution guidelines](https://github.com/GEWIS/.github/blob/main/CONTRIBUTING.md).

### Useful Commands During Development
While developing, use these commonly used commands from the Makefile:

- `make bash` - Shell into the FrankenPHP `web` container.
- `make sf c='...'` - Run a Symfony console command inside the container (e.g. `make sf c=doctrine:migrations:migrate`).
- `make composer c='...'` - Run a Composer command inside the container (e.g. `make composer c=update`).
- `make translations` - Extract translatable strings into the `.xlf` files. Run this whenever you add or edit a user-facing string in PHP, Twig, or a form type.
- `make lint` / `make lint-fix` - Run PHP_CodeSniffer (or PHPCBF to autofix) against the project's coding standard.
- `make phpstan` - Perform static analysis using PHPStan.
- `make psalm` - Perform static analysis using Psalm.
- `make test` - Run the test suite with PHPUnit.
- `make igor` - Run Igor to validate the codebase for FrankenPHP's worker mode.

For a complete list of available commands, run `make help`.

> [!TIP]
> If you are using AI coding tools (Claude Code, Copilot, Cursor, ...), they will pick up `AGENTS.md` automatically. It documents architecture, conventions, and gotchas in more depth than this README. However, it is not only for AI coding tools, have a look too if you are interested.

### Project Structure
A general overview of important folders required for the functioning of the website:

```txt
./
├── config                  # Global configuration files for the website.
├── data                    # Persistent private data-related files, such as cryptographic keys and logs.
├── docker                  # Docker-related files to construct the containers.
├── src                     # Contains the modules that make up the website, each providing specific features.
└── public                  # Publicly accessible files, including the entry point (index.php).
```

We make use of the Model-View-Controller framework. Generally speaking, the model layer is responsible for the interaction with the database and data manipulation. Next, the view layer is responsible for rendering data into a web page. The controller is responsible for processing the request and interacts with the model and view layer to provide a response.

## License
This software is licensed under the GNU General Public License v3.0 (GPL-3.0), see [LICENSE](./LICENSE.txt).
