# Python Panel

Simple web-based dashboard for managing and monitoring Python-based projects.

## Prerequisites & Installation

Run these commands on a fresh Debian/Ubuntu server as root or with `sudo`:

```bash
apt update
apt install apache2 -y
apt install php php-cli php-common libapache2-mod-php php-zip php-mbstring php-json php-curl -y
apt install -y python3 python3-pip python3-venv
apt install -y screen
```

## Apache & PHP Setup

Enable PHP support in Apache and restart the service:

```bash
a2enmod php*
systemctl restart apache2
```

Then copy this project into Apache's web root (for example `/var/www/html/Python-Panel`) and ensure permissions allow the web server user (often `www-data`) to read the files.

## Cron Setup

To enable the cron monitoring for this panel, copy the `cron_status` file from the project root into the system cron directory:

```bash
cp /var/www/html/Python-Panel/cron_status /etc/cron.d/cron_status
chmod 644 /etc/cron.d/cron_status
chown root:root /etc/cron.d/cron_status
systemctl restart cron
```

Adjust the source path if you deploy the panel somewhere other than `/var/www/html/Python-Panel`.

## Usage

Once Apache is running and the panel is deployed (for example at `http://your-server/Python-Panel`):

1. Open the panel in your browser.
2. Click **Login** or **Register** to create an account (passwords are exactly 8 alphanumeric characters).
3. After login you will be redirected to the dashboard.

From the dashboard:

- Create a new project by giving it a name and Python start file (for example `main.py`).
- Edit the project and upload a ZIP containing your bot code and optional `requirements.txt`.
- Start and stop bots using the play/stop controls; each bot runs in its own Linux `screen` session.
- Use the Screen column **View** action to open a live log viewer for `screen.log` in a full-height modal.
- Manage your account from the user menu (change password or delete account).

