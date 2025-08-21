-- CreateTable
CREATE TABLE `repositories` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(512) NOT NULL,
    `description` TEXT NULL,
    `language` VARCHAR(100) NULL,
    `stars` INTEGER NOT NULL DEFAULT 0,
    `forks` INTEGER NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `repositories_full_name_key`(`full_name`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `issues` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `repo_id` INTEGER NOT NULL,
    `number` INTEGER NOT NULL,
    `title` VARCHAR(512) NOT NULL,
    `state` VARCHAR(50) NOT NULL,
    `body` LONGTEXT NULL,
    `author` VARCHAR(255) NOT NULL,
    `labels` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `issues_repo_id_number_key`(`repo_id`, `number`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `pull_requests` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `repo_id` INTEGER NOT NULL,
    `number` INTEGER NOT NULL,
    `title` VARCHAR(512) NOT NULL,
    `state` VARCHAR(50) NOT NULL,
    `body` LONGTEXT NULL,
    `author` VARCHAR(255) NOT NULL,
    `merged_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `pull_requests_repo_id_number_key`(`repo_id`, `number`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `commits` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `repo_id` INTEGER NOT NULL,
    `sha` VARCHAR(40) NOT NULL,
    `message` TEXT NOT NULL,
    `author` VARCHAR(255) NOT NULL,
    `date` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `commits_sha_key`(`sha`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `readme_content` (
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `repo_id` INTEGER NOT NULL,
    `content` LONGTEXT NOT NULL,
    `last_updated` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `readme_content_repo_id_key`(`repo_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `issues` ADD CONSTRAINT `issues_repo_id_fkey` FOREIGN KEY (`repo_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pull_requests` ADD CONSTRAINT `pull_requests_repo_id_fkey` FOREIGN KEY (`repo_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `commits` ADD CONSTRAINT `commits_repo_id_fkey` FOREIGN KEY (`repo_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `readme_content` ADD CONSTRAINT `readme_content_repo_id_fkey` FOREIGN KEY (`repo_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
