PORT ?= 8081
OUTPUT ?= output
FLAGS ?=
MAKE = make FLAGS=$(FLAGS)

# First goal is the default with `make`.

# List make scripts.
list:
	grep "^[^[:space:]]*:" Makefile --before-context=1 --group-separator=""

## Using make dependencies is duplicating behaviour but reads better.
# /bin/php php/update-all.php
# Refresh listing and generate HTML.
sco: fetch qr-codes html

# Refresh listing, generate HTML and update listing provider.
all:
	/bin/php php/update-all.php $(FLAGS)

# Refresh listing, but via systemd, and follow logs.
sysd:
	/bin/systemctl start sessioncommunities.service
	/bin/journalctl --follow --unit=sessioncommunities.service

# Fetch room listing.
fetch:
	/bin/php php/fetch-servers.php $(FLAGS)

# Fetch room listing without writing to disk.
fetch-dry:
	/bin/php php/fetch-servers.php $(FLAGS) --dry-run

# Skip fetching by using the JSON currently served online
fetch-steal: CURL = torsocks curl --progress-bar
fetch-steal:
	$(CURL) https://sessioncommunities.info/servers.json -o output/servers.json
	$(CURL) https://sessioncommunities.info/tags.json -o output/tags.json

# Generate QR codes from server data.
qr-codes:
	/bin/php php/generate-qr-codes.php $(FLAGS)

# Generate HTML from data.
html:
	/bin/php php/generate-html.php $(FLAGS)

# Generate listing provider endpoints from data.
listing:
	/bin/php php/generate-listings.php $(FLAGS)

# Serve a local copy which responds to file changes.
dev: FLAGS = --verbose
dev: open
	$(MAKE) server &
	$(MAKE) watchdog

# (Last item run in foreground to receive interrupts.)

# Serve a local copy on LAN which responds to file changes.
lan-dev: FLAGS = --verbose
lan-dev: open
	-which ip 1>/dev/null 2>/dev/null && ip addr | fgrep -e ' 192.' -e ' 10.' || true
	$(MAKE) lan-server &
	$(MAKE) watchdog

# Serve a local copy.
server:
	/bin/php -S "localhost:$(PORT)" -t "$(OUTPUT)" -q

# Serve a local copy on all interfaces.
lan-server:
	/bin/php -S "0.0.0.0:$(PORT)" -t "$(OUTPUT)" -q

# Open locally served page in browser.
open:
	nohup xdg-open "http://localhost:$(PORT)" >/dev/null 2>/dev/null


# Update Doxygen documentation.
docs-once:
	doxygen -q

# Update Doxygen documentation on file change.
docs:
	$(MAKE) WATCHCMD="$(MAKE) docs-once" watchdog |& sed "s:`realpath .`::"

# Update Doxygen documentation on change and show in browser.
dev-docs: PORT = 8082
dev-docs: OUTPUT = .doxygen/html
dev-docs: open
	$(MAKE) PORT=$(PORT) OUTPUT=$(OUTPUT) server &
	$(MAKE) docs

# Update HTML on file change.
watchdog: WATCHCMD = $(MAKE) html
watchdog:
	set -o pipefail; \
	while :; do find . | grep -v ".git" | entr -nds "$(WATCHCMD)" && exit; done

# Remove artefacts
clean:
	-awk '/^[^#]/ { printf " -e \"!%s\"", $$0 }' <etc/.gitpreserve | xargs git clean -Xdf $(CLEANFLAGS)

# Display files affected by artefact removal.
would-clean: CLEANFLAGS="-n"
would-clean: clean

# Build everything from scratch and test functionality.
test: FLAGS = --verbose
test: clean all open server

# Build everything from scratch and test functionality on LAN.
test-lan: FLAGS = --verbose
test-lan: clean all open lan-server

# Run basic tests without launching site in browser.
test-noninteractive: FLAGS = --verbose
test-noninteractive: clean all

# Run Continuous Integration tests.
test-ci: FLAGS = --verbose --no-color
test-ci: clean all

# Install systemd service and timer.
install-systemd:
	sudo etc/systemd/systemd-install.sh

# -- Aliases --
serve: server

lan-serve: lan-server

data: fetch

watch: watchdog

