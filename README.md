# update-check-runner

The containers that run updates for [violinist.io](https://violinist.io), a PHP / Composer depdency updater for Bitbucket / GitHub / Gitlab / self hosted Gitlab.

[![Tests](https://github.com/violinist-dev/update-check-runner/actions/workflows/test.yml/badge.svg)](https://github.com/violinist-dev/update-check-runner/actions/workflows/test.yml)
[![Violinist enabled](https://img.shields.io/badge/violinist-enabled-brightgreen.svg)](https://violinist.io)
[![violinist-dev/update-check-runner/update-check-runner](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Feiriksm%2Fghcr-pulls%2Fmaster%2Findex.json&query=%24%5B%3F(%40.owner%3D%3D%22violinist-dev%22%20%26%26%20%40.repo%3D%3D%22update-check-runner%22%20%26%26%20%40.image%3D%3D%22update-check-runner%22)%5D.pulls&label=docker%20pulls)](https://github.com/violinist-dev/update-check-runner/pkgs/container/update-check-runner)

## Quick start

tl;dr (example for Gitlab, but similarly applies to GitHub, Bitbucket and self hosted Gitlab as well):

```bash
docker run \
  --pull=always \
  -e "LICENCE_KEY=my_key" \
  -e "REPO_URL=https://gitlab.com/user/repo" \
  -e "USER_TOKEN=glpat-jjYgGb_1npvkiHTdnM" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```

### 0. Find a repository to check for updates

Copy the URL of the repository you want to check for updates. For the purpose of this quick start guide, let's assume this is `https://gitlab.com/user/repo`.

### 1. Obtain a personal access token

- For GitHub visit [https://github.com/settings/tokens/new](https://github.com/settings/tokens/new)
- For Bitbucket visit [https://bitbucket.org/account/settings/app-passwords/new](https://bitbucket.org/account/settings/app-passwords/new). Please note your argument to running updates must include your username (see notice below).
- For Gitlab visit [https://gitlab.com/-/user_settings/personal_access_tokens](https://gitlab.com/-/user_settings/personal_access_tokens)
- For self hosted Gitlab visit [https://gitlab.example.com/-/user_settings/personal_access_tokens](https://gitlab.example.com/-/user_settings/personal_access_tokens) (replace with your own domain)

For the purpose of this quick start guide let's assume the token is `glpat-jjYgGb_1npvkiHTdnM` (this token is totally made up).

> 🚨️ For bitbucket your token should include both your username and your application password separated with a colon (`:`). For example `user:p455w0r0`.

### 2. Obtain a license key from violinist.io

You can do this in one of the following ways:

- Use a license key somehow provided to you
- Use a trial license from [https://violinist.io/trial](https://violinist.io/trial)
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
  -e "REPO_URL=https://gitlab.com/user/repo" \
  -e "USER_TOKEN=glpat-jjYgGb_1npvkiHTdnM" \
  ghcr.io/violinist-dev/update-check-runner:8.3-multi-composer-2
```

## Licence

Licenced under the commercial [violinist licence](https://github.com/violinist-dev/update-check-runner/blob/main/LICENSE)
