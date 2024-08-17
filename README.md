# Shared-Secrets

Shared-Secrets is an application that helps you to simply share one-time secrets over the web. Typically when you do not have the possibility to open an encrypted communication channel (e.g. GPG-encrypted mail) to transfer sensitive information you have to resort to unencrypted means of communication - e.g. SMS, unencrypted e-mail, telephone, etc.

Using the Shared-Secrets service allows you to transfer the actual secret in an encrypted form. Retrieving the secret is as simple as following a link. In contrast to other secret sharing services, Shared-Secrets does not store the secret on the server, but puts the encrypted secret into the link that you share with the desired recipient. That means that the compromise of a Shared-Secrets server does not automatically compromise all of the shared secrets.

Secrets can only be retrieved once. Further retrievals are rejected by matching the encrypted secret against the fingerprints of the secrets that have been retrieved before. By disallowing repeated retrievals of a secret, it is at least possible to detect when the confidentiality of a secret sharing link has been compromised.

To protect your secret from getting known by the server or an attacker, you can additionally protect the secret with a password before sharing it. The secret will be encrypted and decrypted locally without an interaction with the server. You can provide the chosen password to the recipient through a second communication channel to prevent an attacker that is able to control one communication channel from compromising the confidentiallity of your secret.

## Usage

### Share a Secret

Simply enter your secret on the default page of the Shared-Secrets service. You can decide to password-protect the entered secret before sending it to the server by checking the "Password-protected:" box, entering your password and pressing the "Protect!" button. After that, press the "Share the Secret!" button. The secret will be encrypted and converted into a secret sharing link. In cases where you need the plain secret sharing link to be returned by the web  page you can append the GET parameter `?plain` to the URL of the default page.

Secret sharing links can also be created by using a simple POST request:

```
curl -X POST -d "plain&secret=<secret>" https://example.com/

# OR #

curl -X POST -d "secret=<secret>" https://example.com/?plain
```

### Read a Secret

To retrieve the secret, simply open the secret sharing link and press the "Read the Secret!" button. Should your secret be password-protected, check the "Password-protected:" box, enter your password and read your actual secret by pressing the "Unprotect!" button. In cases where you need the plain secret to be returned by the web page you can append the GET parameter `?plain` to the secret sharing link **but be aware** that returning the plain secret does not support the browser-based decryption.

Secrets can also be retrieved using a simple POST request:

```
curl -X POST -d "plain" <secret-sharing-link>

# OR #

curl -X POST <secret-sharing-link>?plain
```

### Download the Public Key

To download the public key of a Shared-Secrets instance in order to manually generate secret sharing links, simply visit the `/pub` page. In cases where you need the plain public key to be returned by the web page you can append the GET parameter `?plain` to the URL.

The public key can also be downloaded using a simple GET request:

```
curl -X GET https://example.com/pub?plain
```

## Installation

### Important!

You **should not** publish the application to the internet without TLS enabled. Depending on your setup you either want to configure the NGINX host in `./defaults/nginx/sites/default.conf` to enable TLS or you want to put a reverse proxy in front of the application which handles TLS termination. An example configuration for an NGINX reverse proxy is provided in `./defaults/nginx/sites/default-tls.conf`.

### Encryption

You should generate a fresh RSA key pair with a minimum key size of 2048 bits:

```
openssl genrsa 2048
```

**Beware:** You should place the RSA private key in a location so that it is not accessible through the webserver. The recommended protection is to directly insert it as a strings into the `RSA_PRIVATE_KEYS` array within the configuration file.

### Recommended Setup

Shared-Secrets comes with its own NGINX+PHP-FPM container based on the `alpine:latest` image. By default Shared-Secrets uses an SQLite database which is sufficient for setups [with low traffic volume](https://www.sqlite.org/whentouse.html#website).

#### Container Build

You can build the container locally:

```
podman build \
  --no-cache \
  --tag "shared-secrets" \
  .
```

#### Container Run

When starting the container you can configure the application through environment variables. For persistent setups you want to mount folders to `/config` and `/db` so that the contained files do not get lost during a container update:

```
podman run \
  --detach \
  --env DEBUG_MODE=false \
  --env DEFAULT_TIMEZONE="Europe/Berlin" \
  --env IMPRINT_TEXT="Who provides this service?" \
  --env IMPRINT_URL="http://127.0.0.1/" \
  --env JUMBO_SECRETS=false \
  --env MYSQL_DB=null
  --env MYSQL_HOST="localhost"
  --env MYSQL_PASS=null
  --env MYSQL_PORT=3306
  --env MYSQL_USER=null
  --env READ_ONLY=false \
  --env RSA_PRIVATE_KEYS="$(openssl genrsa 4096)" \
  --env SHARE_ONLY=false \
  --env SERVICE_TITLE="Shared-Secrets" \
  --env SERVICE_URL="http://127.0.0.1/" \
  --env SQLITE_PATH="%{ROOT_DIR}/db/db.sqlite" \
  --init \
  --name "shared-secrets" \
  --network "slirp4netns:allow_host_loopback=true,cidr=10.0.2.0/24" \
  --publish "127.0.0.1:80:80" \
  --volume "/path/to/your/config:/config" \
  --volume "/path/to/your/db:/db" \
  "localhost/shared-secrets:latest"
```

**Beware:** In the example provided above the `RSA_PRIVATE_KEYS` environment variable is dynamically generated during each execution. In a production setup you want to manually define the RSA keys and manage [key rollovers](#key-rollover) carefully.

### Manual Setup

Shared-Secrets is based on MariaDB/MySQL or SQLite, Nginx and PHP.

#### Files

You only have to deploy the files located in `./html/`.

#### NGINX

An example configuration for NGINX is provided in `./defaults/nginx/`.

#### PHP-FPM

An example configuration for PHP-FPM is provided in `./defaults/php-fpm/`.

#### Database

Shared-Secrets uses a single-table database to store which secret has been retrieved at what point in time. No actual secret content is stored in the database. SQLite will be used if you do not provide any MariaDB/MySQL credentials. You have to manually create the database if you want to use MariaDB/MySQL instead:

```
CREATE DATABASE secrets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE secrets;

CREATE TABLE secrets (
  keyid       VARCHAR(64),
  fingerprint VARCHAR(64),
  time        TIMESTAMP,
  PRIMARY KEY (keyid, fingerprint)
);

GRANT ALL ON secrets.* TO 'secrets'@'%'         IDENTIFIED BY '5TR0NGP455W0RD!';
GRANT ALL ON secrets.* TO 'secrets'@'localhost' IDENTIFIED BY '5TR0NGP455W0RD!';
GRANT ALL ON secrets.* TO 'secrets'@'127.0.0.1' IDENTIFIED BY '5TR0NGP455W0RD!';
GRANT ALL ON secrets.* TO 'secrets'@'::1'       IDENTIFIED BY '5TR0NGP455W0RD!';

FLUSH PRIVILEGES;

EXIT;
```

## Configuration

There are several ways to configure the application with configuration sources on the top take precendence over sources on the bottom of the list:

1. environment variables
2. `./html/config/.env`
3. `./html/.env`
4. `/.env`
5. `./html/config/config.php`

#### Configuration via environment variables

Configuration values can be set by defining corresponding environment variables. Have a look at `./.env.default` for a list of valid environment variables. There are a few things to consider for the configuration via environment variables:

* Strings can contain placeholders like `%{EXAMPLE}` which will be replaced with the corresponding configuration values or environment variables if they have been defined before they are first used in a placeholder. Already-defined configuration values take precedence over environment variables with the same name during the replacement.
* The specific placeholder `%{ROOT_DIR}` points to the execution path of the application.
* Boolean strings (like `true` and `false`) are automatically converted to the boolean type.
* Integer strings are automatically converted to the nteger type.
* The `null` string is automatically converted to the null type.

#### Configuration via `.env` file

Copy the `./.env.default` file to one of the aforementioned locations for the `.env` file and set the necessary configuration values.

#### Configuration via `config.php` file

Copy the `./html/config/config.php.default` file to `./html/config/config.php` and set the necessary configuration values.

### Read-Only and Share-Only Instances

The configuration allows you to set your instances into read-only and/or share-only mode. This can be useful if you want to use a private **share-only** instance or custom software to create secret sharing sharing links but provide a public **read-only** instance to retrieve the generated secret sharing links. There are two more things to consider:

* A **share-only** instance does not need access to the RSA private key as it will not decrypt secret sharing links. Therefore, it is possible to configure the RSA public key of the corresponding **read-only** instance into the `RSA_PRIVATE_KEYS` array of a **share-only** instance.
* The basis for the creation of secret sharing link is the `SERVICE_URL` configuration value. In order for a **share-only** instance to generate correct secret sharing links you have to set the URL of the corresponding **read-only** instance as the `SERVICE_URL` configuration value of the **share-only** instance.

## Maintenance

### Database Backup

It is essential for Shared-Secrets to know which secrets have already been retrieved in order to implement the read-once functionality. Therefore, you should regularly backup your database to prevent messages from being read more than once.

A command to create a backup of all databases of MariaDB/MySQL may look like this:

```
sudo mysqldump --all-databases --result-file="./backup_$(date +'%Y%m%d').sql"
```

**Hint:** To recover from a loss of your database it is important to change the used key pair. Make sure to **not** use the [key rollover](#key-rollover) feature to prevent old secrets from being retrieved more than once.

### Database Optimization

While Shared-Secrets is designed to store a minimal amount of data (keyid, fingerprint of the retrieved message, timestamp) it might become necessary to clean-up the database when a lot of secrets have been retrieved. One approach is as follows:

* Use the [key rollover](#key-rollover) feature to add a new key that is used for all newly shared secrets. (_Users will still be able to retrieve old secrets._)
* Provide a grace period where secrets for the old **and** new key can be retrieved.
* Remove the old key from the list of valid keys. (_Users will **not** be able to retrieve old secrets anymore._)
* Delete the database entries of messages that belong to the old key.

The following commands can be used to delete the database entries of messages that belong to the old key:

```
USE secrets;

DELETE FROM secrets WHERE keyid = "<keyid of the old key>";

OPTIMIZE TABLE secrets;

EXIT;
```

### Key Rollover

Shared-Secrets supports key rollovers in the configuration and in the database. Key rollovers can be useful when you want to switch from an old key to a new key without service interruptions. They allow you to introduce a new key for sharing secrets while still allowing users to retrieve secrets of old keys.

To execute a key rollover you can add more than one RSA private key in the `RSA_PRIVATE_KEYS` configuration value, which happens to be an array. The last element in the array is the new key that is used to create new secret sharing links while all configured keys are used when trying to retrieve secrets. If you do not want to allow the retrieval of secrets created for old keys then you have to remove these specific keys from the `RSA_PRIVATE_KEYS` configuration value.

Therefore, the `RSA_PRIVATE_KEYS` configuration value can look like this:

```
define("RSA_PRIVATE_KEYS", ["-----BEGIN RSA PRIVATE KEY-----\n".
                            "...\n".
                            "...\n".
                            "...\n".
                            "-----END RSA PRIVATE KEY-----",
                            "-----BEGIN RSA PRIVATE KEY-----\n".
                            "...\n".
                            "...\n".
                            "...\n".
                            "-----END RSA PRIVATE KEY-----"]);
```

**Hint:** Key rollovers can be helpful when your database grows too big and needs [to be optimized](#database-optimization).

## Limitations

Using Shared-Secrets is **not** a 100% solution to achieve a perfectly secure communication channel, but it can be an improvement in situations where no better communication channel is available. You should always consider to switch to more secure channels like authenticated e-mail encryption (using GnuPG or S/MIME) or end-to-end encrypted instant messengers (like Signal or Threema).

### Storage Compromise

An attacker gaining access to storage containing secret sharing links could read the stored secret sharing links and try to retrieve the secrets. If properly implemented and used then Shared-Secrets can protect against such an attacker in the following ways:

1. From the secret sharing link itself the attacker will not learn about the contents of the actual secret.
2. When the secret has already been retrieved then the attacker will not be able to retrieve the secret again using the same secret sharing link as Shared-Secrets prevents secrets from being retrieved more than once.
3. When the secret has not already been retrieved and the attacker retrieved the secret instead, then you will be able to notice the attack by not being able to retrieve the secret yourself. Furthermore, the database will contain the information when the secret has been retrieved, providing the possibility to find out when the compromise took place.

### Passive Man-in-the-Middle Attack

A passive man-in-the-middle attacker could read the transmitted secret sharing links and try to retrieve the secrets. If properly implemented and used then Shared-Secrets can protect against such an attacker in the following ways:

1. From the secret sharing link itself the attacker will not learn about the contents of the actual secret.
2. When the secret is retrieved by the attacker, then you will be able to notice the attack by not being able to retrieve the secret yourself. Furthermore, the database will contain the information when the secret has been retrieved, providing the possibility to find out when the compromise took place.

### Active Man-in-the-Middle Attack (Scenario A)

An active man-in-the-middle attacker could change the transmitted secret sharing links in a way that they point to a malicious server that acts as a proxy between you and the actual Shared-Secrets server. By calling the modified secret sharing links you would provide the URLs to the malicious server which would then transparently direct the requests to the actual Shared-Secrets server and return the retrieved secrets while also storing them for the attacker. In such a scenario you would not easily notice that the secrets have been compromised. If properly implemented and used then Shared-Secrets can protect against such an attacker in the following way:

Shared-Secrets provides a browser-based encryption and decryption that is executed locally. Using this additional layer of encryption would prevent the malicious server from reading the decrypted secret. However, an active man-in-the-middle attacker would also be able to compromise the browser-based decryption. In order to mitigate the compromise of the local decryption in cases where you cannot find out if the Shared-Secret server is legitimate, the following strategy might be helpful:

1. Open a fresh **private** browsing window (also known as _"incognito mode"_).
3. Retrieve the secret.
4. Go offline with your computer. Do **not** forget to disable wireless connections or to unplug wired connections.
5. Locally decrypt the retrieved secret.
6. Take note of the locally decrypted secret.
7. Close the private browsing window.
8. Now you can go online with your computer again.

However, the better solution to this problem would be to decrypt the retrieved secret outside of the browser. Unfortunately, this would require the usage of additional tooling.

### Active Man-in-the-Middle Attack (Scenario B)

An active man-in-the-middle attacker could change the transmitted secret sharing links in a way that they retrieve the secrets and then create new secret sharing links containing the retrieved secrets using the same Shared-Secrets server. In such a scenario you would not easily notice that the secrets have been compromised. If properly implemented and used then Shared-Secrets can protect against such an attacker in the following ways:

1. Shared-Secrets provides a browser-based encryption and decryption that is executed locally. Using this additional layer of encryption would prevent the attacker from reading the decrypted secret.
2. Shared-Secrets provides the possibility to create separate **share-only** and **read-only** instances. By having a **share-only** instance that is **not** publicly available and a **read-only** instance that is publicly available the attacker would be able to retrieve the secret but would not be able to create a new secret sharing link.

## Attributions

* [Bootstrap](https://getbootstrap.com): for providing an easy-to-use framework to build nice-looking applications

## License

This application is released under the BSD license. See the [LICENSE](LICENSE) file for further information.
