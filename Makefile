WP_PATH=$(shell pwd)/web
REPO_PATH=$(shell pwd)

SITE_URL=http://calliweb.local:8080
SITE_TITLE=calliweb Local

DB_NAME=jointla
DB_USER=root
DB_PASSWORD=
DB_HOST=localhost

ADMIN_USER=admin
ADMIN_PASSWORD=admin
ADMIN_EMAIL=admin@xenomedia.com

THEME_NAME=astra-child
BASE_THEME=astra

PROD_SSH=xeno-tla-live
STAGING_SSH=$(PROD_SSH) # Staging is on the same server, adjust if different
PROD_PATH=~/web
PROD_PLUGINS_PATH=$(PROD_PATH)/wp-content/plugins

LOCAL_PATH=$(shell pwd)/web
LOCAL_TEST=$(shell pwd)/tests

STAGING_PATH=~/staging
STAGING_PLUGINS_PATH=$(STAGING_PATH)/wp-content/plugins

LINK_PLUGINS=tla-connector astra-addon rhythm-sso maps-marker-pro ultimate-elementor elementor-pro
PRO_PLUGINS=astra-addon rhythm-sso maps-marker-pro ultimate-elementor elementor-pro

BACKUP_DATE := $(shell date +%Y-%m-%d-%H%M%S)
BACKUP_FILE := jointla_db_$(BACKUP_DATE).sql
BACKUP_ZIP := jointla_db_$(BACKUP_DATE).zip

LOCAL_BACKUP_DIR=backups
AWS_PROFILE=jointla

.PHONY: init-backup-dir backup-production download-backup upload-to-s3 cleanup-production backup-production-to-s3 install-wp config-wp setup-wp symlinks install-parent-theme activate-child-theme install-plugins activate-plugins permalinks serve rsync-pro-plugins-from-prod-to-local update-staging update-and-test-staging confirm-production update-production update-and-test-production sync-pro-plugins-to-staging test-staging test-production setup-local

# --- Ensure local backup dir exists ---
init-backup-dir:
	mkdir -p $(LOCAL_BACKUP_DIR)

# --- Step 1: Create backup on production ---
backup-production:
	@echo "Creating DB backup on production..."
	ssh $(PROD_SSH) "cd $(PROD_PATH) && wp db export $(BACKUP_FILE)"
	ssh $(PROD_SSH) "cd $(PROD_PATH) && zip $(BACKUP_ZIP) $(BACKUP_FILE)"
	ssh $(PROD_SSH) "cd $(PROD_PATH) && rm $(BACKUP_FILE)"

# --- Step 2: Download to local ---
download-backup: init-backup-dir
	@echo "Downloading backup to local..."
	scp $(PROD_SSH):$(PROD_PATH)/$(BACKUP_ZIP) $(LOCAL_BACKUP_DIR)/$(BACKUP_ZIP)

# --- Step 3: Upload to S3 ---
upload-to-s3:
	@echo "Uploading to S3..."
	aws s3 cp $(LOCAL_BACKUP_DIR)/$(BACKUP_ZIP) \
		s3://jointla-wp-backups/$(BACKUP_ZIP) \
		--profile $(AWS_PROFILE)

# --- Step 4: Cleanup production (optional but recommended) ---
cleanup-production:
	@echo "Cleaning up production backup..."
	ssh $(PROD_SSH) "rm $(PROD_PATH)/$(BACKUP_ZIP)"

# --- Backup One command ---
backup-production-to-s3: backup-production download-backup upload-to-s3 cleanup-production
	@echo "Backup completed and uploaded to S3."

# --- WordPress core ---
install-wp:
	mkdir -p $(WP_PATH)
	wp core download --path=$(WP_PATH) --force

config-wp:
	wp config create \
		--path=$(WP_PATH) \
		--dbname=$(DB_NAME) \
		--dbuser=$(DB_USER) \
		--dbpass="$(DB_PASSWORD)" \
		--dbhost=$(DB_HOST) \
		--skip-check \
		--force

setup-wp:
	wp core install \
		--path=$(WP_PATH) \
		--url=$(SITE_URL) \
		--title="$(SITE_TITLE)" \
		--admin_user=$(ADMIN_USER) \
		--admin_password="$(ADMIN_PASSWORD)" \
		--admin_email=$(ADMIN_EMAIL) || true

# --- Symlinks ---
symlinks:
	@echo "Creating symlinks..."
	mkdir -p $(WP_PATH)/wp-content/themes
	mkdir -p $(WP_PATH)/wp-content/plugins

	ln -sfn $(REPO_PATH)/wp-content/themes/$(THEME_NAME) \
		$(WP_PATH)/wp-content/themes/$(THEME_NAME)

	@for plugin in $(LINK_PLUGINS); do \
		echo "Linking $$plugin..."; \
		ln -sfn $(REPO_PATH)/wp-content/plugins/$$plugin \
		$(WP_PATH)/wp-content/plugins/$$plugin; \
	done

	@echo "Symlinks created."

# --- Theme ---
install-parent-theme:
	@echo "Installing Astra..."
	wp theme install $(BASE_THEME) --path=$(WP_PATH) --force

activate-child-theme:
	wp theme activate $(THEME_NAME) --path=$(WP_PATH)

# --- Plugins ---
install-plugins:
	wp plugin install advanced-custom-fields --activate --force --path=$(WP_PATH)
	wp plugin install akismet --activate --force --path=$(WP_PATH)
	wp plugin install auth0 --activate --force --path=$(WP_PATH)
	wp plugin install codepress-admin-columns --activate --force --path=$(WP_PATH)
	wp plugin install cookie-law-info --activate --force --path=$(WP_PATH)
	wp plugin install elementor --activate --force --path=$(WP_PATH)
	wp plugin install header-footer-elementor --activate --force --path=$(WP_PATH)
	wp plugin install members --activate --force --path=$(WP_PATH)
	wp plugin install pdf-print --activate --force --path=$(WP_PATH)
	wp plugin install regenerate-thumbnails --activate --force --path=$(WP_PATH)
	wp plugin install sg-security --activate --force --path=$(WP_PATH)
	wp plugin install the-events-calendar --activate --force --path=$(WP_PATH)
	wp plugin install updraftplus --activate --force --path=$(WP_PATH)
	wp plugin install user-private-files --activate --force --path=$(WP_PATH)
	wp plugin install wordpress-importer --activate --force --path=$(WP_PATH)
	wp plugin install wordpress-seo --activate --force --path=$(WP_PATH)
	wp plugin install wp-optimize --activate --force --path=$(WP_PATH)

# --- Activate all (including PRO plugins) ---
activate-plugins:
	@echo "Activating all plugins..."
	wp plugin activate --all --path=$(WP_PATH)

# --- Permalinks ---
permalinks:
	wp rewrite structure '/%postname%/' --hard --path=$(WP_PATH)
	wp rewrite flush --hard --path=$(WP_PATH)

# --- Run local server ---
serve:
	wp server --host=jointla.local --port=8001 --path=$(WP_PATH)

sync-pro-plugins-to-staging:
	@echo "Syncing pro plugins..."

	@for plugin in $(PRO_PLUGINS); do \
		echo "Syncing $$plugin..."; \
		ssh $(PROD_SSH) "\
			rsync -avz --delete \
			$(PROD_PLUGINS_PATH)/$$plugin/ \
			$(STAGING_PLUGINS_PATH)/$$plugin/"; \
	done

	@echo "Done."

# --- Update PRO plugins from live to local using rsync ---
rsync-pro-plugins-from-prod-to-local:
	@for plugin in $(PRO_PLUGINS); do \
		echo "Updating $$plugin..."; \
		rsync -avz --delete $(PROD_SSH):$(PROD_PLUGINS_PATH)/$$plugin/ $(REPO_PATH)/wp-content/plugins/$$plugin/; \
	done

# ---- Update plugins via wp cli ---
update-staging:
	@echo "Updating WordPress core on staging..."
	ssh $(STAGING_SSH) "cd $(STAGING_PATH) && wp core update"

	@echo "Updating themes..."
	ssh $(STAGING_SSH) "cd $(STAGING_PATH) && wp theme update --all"

	@echo "Updating plugins..."
	ssh $(STAGING_SSH) "cd $(STAGING_PATH) && wp plugin update --all"

	@echo "Staging update complete."

update-and-test-staging: sync-pro-plugins-to-staging update-staging test-staging
	@echo "All good in staging"

confirm-production:
	@read -p "This will BACKUP and UPDATE PRODUCTION. Continue? [y/N] " ans; \
	[ "$$ans" = "y" ]

update-production: confirm-production
	@echo "Updating WordPress core on production..."
	ssh $(PROD_SSH) "cd $(PROD_PATH) && wp core update"

	@echo "Updating themes on production..."
	ssh $(PROD_SSH) "cd $(PROD_PATH) && wp theme update --all"

	@echo "Updating plugins on production..."
	ssh $(PROD_SSH) "cd $(PROD_PATH) && wp plugin update --all"

	@echo "Production update complete."

update-and-test-production: update-production test-production
	@echo "All good in production."

snapshot:
	@read -p "Commit and push snapshot to main? [y/N] " ans; \
	if [ "$$ans" = "y" ]; then \
		git add .; \
		git commit -m "Snapshot after production update $(shell date +%Y-%m-%d-%H%M%S)" || echo "Nothing to commit"; \
		git push origin main; \
	fi

updates:
	@echo "Starting full update workflow..."
	@$(MAKE) backup-production-to-s3
	@$(MAKE) update-and-test-staging
	@$(MAKE) update-and-test-production
	@$(MAKE) rsync-pro-plugins-from-prod-to-local
	@$(MAKE) snapshot
	@echo "All updates complete."

# --- Tests ---
test-staging:
	@echo "Running Behat tests against staging..."
	cd $(LOCAL_TEST) && vendor/bin/behat --profile=staging

test-production:
	@echo "Running Behat tests against production..."
	cd $(LOCAL_TEST) && vendor/bin/behat --profile=production

# --- Full setup ---
setup-local: install-wp config-wp setup-wp install-parent-theme symlinks activate-child-theme install-plugins activate-plugins permalinks