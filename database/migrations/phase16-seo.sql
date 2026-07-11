-- Sigma Panels & Paint - Phase 16 SEO migration (SAFE / idempotent)
-- Adds advanced SEO + site-icon columns. Uses ADD COLUMN IF NOT EXISTS
-- (MariaDB 10.0.2+ / XAMPP) so it can be run repeatedly with no data loss.
-- Does NOT drop tables or delete rows.

ALTER TABLE `seo_pages`
    ADD COLUMN IF NOT EXISTS `canonical_url`       VARCHAR(255) NULL AFTER `meta_keywords`,
    ADD COLUMN IF NOT EXISTS `robots_noindex`      TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `robots_nofollow`     TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `og_title`            VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `og_description`      TEXT NULL,
    ADD COLUMN IF NOT EXISTS `og_image`            VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `twitter_title`       VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `twitter_description` TEXT NULL,
    ADD COLUMN IF NOT EXISTS `twitter_image`       VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `schema_json`         LONGTEXT NULL;

ALTER TABLE `business_settings`
    ADD COLUMN IF NOT EXISTS `favicon_path`             VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `apple_touch_icon_path`    VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `site_icon_path`           VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `default_og_image`         VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `default_twitter_image`    VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `google_site_verification` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `bing_site_verification`   VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `robots_txt`               TEXT NULL;
