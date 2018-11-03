# dehydrated configurations for invis-servers

# Resolve names to addresses of IP version only. (curl)
# supported values: 4, 6
# default: <unset>
IP_VERSION=4

# Output directory for challenge-tokens to be served by webserver or deployed in HOOK (default: /var/www/dehydrated)
WELLKNOWN=/srv/www/htdocs/dehydrated/.well-known/acme-challenge

# E-mail to use during the registration (default: <unset>)
CONTACT_EMAIL=adminmail
