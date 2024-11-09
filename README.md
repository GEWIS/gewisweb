<div align="center">
    <h1>GEWISWEB - The GEWIS Website</h1>

<!-- Shield group -->
[![Latest Release](https://img.shields.io/github/v/release/GEWIS/gewisweb)](https://github.com/GEWIS/gewisweb/releases)
[![Build](https://img.shields.io/github/check-runs/GEWIS/gewisweb/main)](https://github.com/GEWIS/gewisweb/actions)
[![Uptime](https://uptime.gewis.nl/api/badge/1/uptime)](https://gewis.nl/en/)
[![Issues](https://img.shields.io/github/issues/GEWIS/gewisweb)](https://github.com/GEWIS/gewisweb/issues)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/GEWIS/gewisweb/main)](https://github.com/GEWIS/gewisweb/commits/main)
[![License](https://img.shields.io/github/license/GEWIS/gewisweb.svg)](./LICENSE.txt)

<p>GEWISWEB is the <a href="https://gewis.nl" target="_blank">website</a> created by and for the members of GEWIS — <em>GEmeenschap van Wiskunde en Informatica Studenten</em>.</p>
</div>

## Features
The GEWIS website provides its members and other visitors with lots of functionality:

- **Activities**:
    - Create activities with a wide range of options for sign-up lists.
    - Enables members to sign up for various events and activities, enhancing engagement and participation.

- **Companies**:
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
GEWISWEB is built on PHP and the [Laminas MVC framework](https://getlaminas.org/). The Laminas MVC framework provides a solid foundation for building scalable and maintainable web applications.

### Prerequisites
We recommend developing natively on a Linux machine or through WSL2 on Windows (note: Arch-based distributions are **not** recommended) with the [PhpStorm](https://www.jetbrains.com/phpstorm/) IDE or another IDE with good IntelliSense support for PHP.

You will need at least:
- `docker` and `docker compose` (make sure that you have enabled [Buildkit](https://docs.docker.com/build/buildkit/#getting-started))
- `gettext` utilities
- `git`
- `make`
- A `.po` file editor (e.g. POEdit)

Some of the `make` commands run natively on your machine; as such, you may also need to install PHP itself (use the `ondrej/php` PPA for `apt` to get the latest version) and [`composer`](https://getcomposer.org/download/).

### Installation
To set up GEWISWEB locally, follow these steps:

1. [Fork the repository](https://github.com/GEWIS/gewisweb/fork).
2. Clone your fork (`git clone git@github.com:{username}/gewisweb.git`).
3. Copy the `.env.dist` file to `.env` and alter the file to your needs.
4. Run `make rundev` to build and serve the website (this may take 5-10 minutes).
5. Run `make migrate` and `make seed` to get some test data.
6. Go to `http://localhost/` in your browser and you are greeted with the GEWIS website.
7. Log in with membership number `8000` and the password `gewiswebgewis`.

### Contributing
We welcome contributions from the community, especially GEWIS members! To contribute:

1. Perform the steps from [Installation](#installation).
2. Create your feature of bug fix branch (`git switch -c feature/my-amazing-feature`).
3. Commit your changes (`git commit -m 'feat: added my amazing feature'`).
4. Push to the branch (`git push origin feature/my-amazing-feature`).
5. Open a pull request.

More detailed information on GEWIS' contribution guidelines, including conventions on branch names and commit messages, can be found [here](https://github.com/GEWIS/.github/blob/main/CONTRIBUTING.md).

### Project Structure
A general overview of important folders required for the functioning of the website:

```txt
./
├── config                  # Global configuration files for the website.
├── data                    # Persistent private data-related files, such as cryptographic keys and logs.
├── docker                  # Docker-related files to construct the containers.
├── module                  # Contains the modules that make up the website, each providing specific features.
└── public                  # Publicly accessible files, including the entry point (index.php).
```

We make use of the Model-View-Controller framework. Generally speaking, the model layer is responsible for the interaction with the database and data manipulation. Next, the view layer is responsible for rendering data into a web page. The controller is responsible for processing the request and interacts with the model and view layer to provide a response.

To make development easier (and due to how the Laminas MVC framework works) we add some extra layers and arrive at a structure for each module that looks like this:

```txt
./
├── config
│   └── module.config.php   # Contains routing information and other module specific configurations.
├── src
│   ├── Command             # CLI commands.
│   │   └── ...
│   ├── Controller          # Entrypoint for requests to the website, some light processing takes place here before using a specific service.
│   │   └── ...
│   ├── Form                # Specification and validation of forms based on entities.
│   │   └── ...
│   ├── Mapper              # Doctrine ORM repositories to access the underlying database and mapping entities to that data.
│   │   └── ...
│   ├── Model               # Doctrine ORM entities.
│   │   └── ...
│   └── Service             # Services contain the core logic related to specific entities (or sets of entities) and do most of the processing.
│   │   └── ...
├── test                    # Test files for this module, such as unit tests.
│   ├── Seeder              # Data fixtures to seed the database with data for this module.
│   │   └── ...
│   └── ...
└── view                    # All template files ("views") made out of HTML and PHP code, used by controllers for output.
    └── ...
```

The `Application` module has two additional folders:
- `language` containing the translation files (`make translations` to update them).
- `migrations` containing database migrations.

## License
This software is licensed under the GNU General Public License v3.0 (GPL-3.0), see [LICENSE](./LICENSE.txt).
