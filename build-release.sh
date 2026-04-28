#!/bin/bash
# Build release ZIP with version in filename

set -e

# Extract version from 404-alert.php
VERSION=$(grep "define( 'ALERT404_VERSION'" 404-alert.php | sed -n "s/.*'\([^']*\)'.*/\1/p")
DATE=$(date +%Y%m%d)
FILENAME="404-alert-v${VERSION}-${DATE}.zip"

echo "📦 Building release: $FILENAME"

# Create ZIP with proper structure
zip -r "$FILENAME" \
  404-alert.php \
  includes/ \
  languages/ \
  assets/ \
  templates/ \
  README.md \
  LICENSE \
  INSTALL.md \
  ARCHITECTURE.md \
  CONTRIBUTING.md \
  REDIS.md \
  SMTP.md \
  SMTP-CONNECTION-TEST.md \
  CONFIGURATION-PRODUCTION.md \
  readme.txt \
  -x "*.git*" "vendor/*" "tests/*" "node_modules/*" ".vscode/*" \
  ".phpunit*" "phpcs.xml" "phpstan.neon" "wordpress-local/*" \
  ".env*" "*.local" "SUBMISSION-CHECKLIST.md" "WORDPRESS-*.md" \
  "VERSION-*.md" "build-release.sh" > /dev/null

# Update symlink
rm -f 404-alert.zip
ln -s "$FILENAME" 404-alert.zip

echo "✅ Release created: $FILENAME"
echo "✅ Symlink updated: 404-alert.zip -> $FILENAME"
