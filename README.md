# update-check-runner

The containers that run updates for [violinist.io](https://violinist.io), a PHP / Composer depdency updater for Bitbucket / GitHub / GitLab / Self Hosted GitLab.

Also available as standalone docker containers to self host the update running.

[![Tests](https://github.com/violinist-dev/update-check-runner/actions/workflows/test.yml/badge.svg)](https://github.com/violinist-dev/update-check-runner/actions/workflows/test.yml)
[![Violinist enabled](https://img.shields.io/badge/violinist-enabled-brightgreen.svg)](https://violinist.io)
[![violinist-dev/update-check-runner/update-check-runner](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Feiriksm%2Fghcr-pulls%2Fmaster%2Findex.json&query=%24%5B%3F(%40.owner%3D%3D%22violinist-dev%22%20%26%26%20%40.repo%3D%3D%22update-check-runner%22%20%26%26%20%40.image%3D%3D%22update-check-runner%22)[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fviolinist-dev%2Fupdate-check-runner.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fviolinist-dev%2Fupdate-check-runner?ref=badge_shield)
%5D.pulls&label=docker%20pulls)](https://github.com/violinist-dev/update-check-runner/pkgs/container/update-check-runner)

## Quick start

Don't even have time for a quick start? Here are some examples:

<details>
  <summary>Example for Gitlab</summary>
  
```bash
docker run \
  --pull=always \
  -e "LICENCE_KEY=my_key" \
  -e "PROJECT_URL=https://gitlab.com/user/repo" \
  -e "USER_TOKEN=glpat-jjYgGb_1npvkiHTdnM" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```
</details>

<details>
  <summary>Example for GitHub</summary>
  
```bash
docker run \
  --pull=always \
  -e "LICENCE_KEY=my_key" \
  -e "PROJECT_URL=https://github.com/user/repo" \
  -e "USER_TOKEN=ghp_jYgGb_1npvkiHTdnM" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```
</details>

<details>
  <summary>Example for Bitbucket</summary>
  
```bash
docker run \
  --pull=always \
  -e "LICENCE_KEY=my_key" \
  -e "PROJECT_URL=https://bitbucket.org/org/project/repo" \
  -e "USER_TOKEN=myusername:app_p455w0rd" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```
</details>


[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fviolinist-dev%2Fupdate-check-runner.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fviolinist-dev%2Fupdate-check-runner?ref=badge_large)

### 0. Find a repository to check for updates

Copy the URL of the repository you want to check for updates. For the purpose of this quick start guide, let's assume this is `https://gitlab.com/user/repo`.

### 1. Obtain an access token / app password

- For GitHub visit [https://github.com/settings/tokens/new](https://github.com/settings/tokens/new)
- For Bitbucket visit [https://bitbucket.org/account/settings/app-passwords/new](https://bitbucket.org/account/settings/app-passwords/new). Please note your argument to running updates must include your username (see notice below).
- For Gitlab visit [https://gitlab.com/-/user_settings/personal_access_tokens](https://gitlab.com/-/user_settings/personal_access_tokens)
- For self hosted Gitlab visit [https://gitlab.example.com/-/user_settings/personal_access_tokens](https://gitlab.example.com/-/user_settings/personal_access_tokens) (replace with your own domain)

For the purpose of this quick start guide let's assume the token is `glpat-jjYgGb_1npvkiHTdnM`.

> 🚨️ For bitbucket your token should include both your username and your application password separated with a colon (`:`). For example `user:p455w0r0`.

### 2. Obtain a license key from violinist.io

You can do this in one of the following ways:

- Use a license key somehow provided to you
- Use a trial license from [https://violinist.io/self-hosted-trial](https://violinist.io/self-hosted-trial)
- Purchase a license on [https://violinist.io/purchase-licence](https://violinist.io/purchase-licence)

For the purpose of this quick start guide, let's assume the license key is `fYtLakIxFEBdy1vB_SU3iaPrTRwVugFnj9AGxRYVsRSha-ju3m7qpFNHhwPn_C5vS38tDGW6jo_DOI7zZfcy5n6cu7_3ef8vU8HyfS6cyrR6Xq767XOcvqb1KKgoCKqo6_vyI02pWk6YgyU3gsrqgaS5pwcVo9aNY2AQbS1TZABJjwWRHCUqNrCK7pTd2TE6hA01rMQKTJUNmjlLjbYlYc4c3TQxS6iqYH8`

### 3. Run the appropriate container 

Choose the PHP version and composer version relevant to your project. For the purpose of this quick start guide, let's assume we use PHP 8.3 and Composer 2.

That means we should run the following docker image:

> ghcr.io/violinist-dev/update-check-runner:`8.3`-multi-composer-`2`

Putting it all together with your noted arguments:

```bash
docker run \
  --pull=always \
  -e "LICENCE_KEY=fYtLakIxFEBdy1vB_SU3iaPrTRwVugFnj9AGxRYVsRSha-ju3m7qpFNHhwPn_C5vS38tDGW6jo_DOI7zZfcy5n6cu7_3ef8vU8HyfS6cyrR6Xq767XOcvqb1KKgoCKqo6_vyI02pWk6YgyU3gsrqgaS5pwcVo9aNY2AQbS1TZABJjwWRHCUqNrCK7pTd2TE6hA01rMQKTJUNmjlLjbYlYc4c3TQxS6iqYH8" \
  -e "PROJECT_URL=https://gitlab.com/user/repo" \
  -e "USER_TOKEN=glpat-jjYgGb_1npvkiHTdnM" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```

## Variables

### At a glance

| Name | Required | Default value |
| -- | -- | -- |
| LICENCE_KEY | Yes | |
| USER_TOKEN | Yes| |
| PROJECT_URL | Yes | |
| GIT_AUTHOR_NAME | No | violinist-bot |
| GIT_AUTHOR_EMAIL | No | violinistdevio@gmail.com |
| GIT_COMMITTER_NAME | No | violinist-bot |
| GIT_COMMITTER_EMAIL | No | violinistdevio@gmail.com |
| ALTERNATE_COMPOSER_PATH | No | |


### Reference

#### LICENCE_KEY 

The licence key either handed to you, obtained or purchased for running your own update runners.

Please note the below key is an example, which is signed with an invalid private key.

Example value: `fYtLakIxFEBdy1vB_SU3iaPrTRwVugFnj9AGxRYVsRSha-ju3m7qpFNHhwPn_C5vS38tDGW6jo_DOI7zZfcy5n6cu7_3ef8vU8HyfS6cyrR6Xq767XOcvqb1KKgoCKqo6_vyI02pWk6YgyU3gsrqgaS5pwcVo9aNY2AQbS1TZABJjwWRHCUqNrCK7pTd2TE6hA01rMQKTJUNmjlLjbYlYc4c3TQxS6iqYH8`

#### USER_TOKEN

A token valid for accessing the API, cloning the repo, pushing branches and creating pull requests on behalf of the user in question. Usually this will be a personal access token (PAT) or an app password.

Example value: `ghp_eIgde31jggU3GIBB22fJbv2odcd0xy0e9jh8`

#### PROJECT_URL

The repository to run update checks on.

Example value: `https://github.com/violinist-dev/update-check-runner`

## FAQ

<details>
  <summary>What is the difference between self hosting and using violinist.io (the SaaS)</summary>

  In practice, all the automation, convenience, logging and persistance you would have to need.
  
  - No formatting, storing or analysis of logs. You would have to implement this yourself if needed.
  - No notifications (email or slack)
  - No automatic discovery of PHP version. When your project upgrade to a new version, you must also change the PHP version of the update container
  - No private keys per project or per organization
</details>

<details>
  <summary>Can I use this to run updates for my clients or customers and charge money for it?</summary>

  Yes. There are no restrictions on what you use the licence key for, and if you use it for commercial purposes or something else.

  You are not allowed to provide the same service as violinist.io (sell licences to this software, or provide a SaaS based on this software). But please go ahead and purchase a licence and charge your customers multiples of that to provide the service you purchased.

  Otherwise, we refer to the licence of this repo: [https://github.com/violinist-dev/update-check-runner/blob/main/LICENSE](https://github.com/violinist-dev/update-check-runner/blob/main/LICENSE)
</details>

## Licence

Licenced under the commercial [violinist licence](https://github.com/violinist-dev/update-check-runner/blob/main/LICENSE)