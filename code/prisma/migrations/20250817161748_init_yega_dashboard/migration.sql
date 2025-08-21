-- CreateEnum
CREATE TYPE "ProjectType" AS ENUM ('CORE_SYSTEM', 'API', 'WEB_APP', 'MOBILE_APP', 'LIBRARY', 'TOOL', 'DOCUMENTATION', 'OTHER');

-- CreateEnum
CREATE TYPE "IssueState" AS ENUM ('OPEN', 'CLOSED');

-- CreateEnum
CREATE TYPE "IssuePriority" AS ENUM ('CRITICAL', 'HIGH', 'MEDIUM', 'LOW');

-- CreateEnum
CREATE TYPE "PullRequestState" AS ENUM ('OPEN', 'CLOSED', 'MERGED');

-- CreateEnum
CREATE TYPE "PullRequestComplexity" AS ENUM ('SIMPLE', 'MODERATE', 'COMPLEX', 'CRITICAL');

-- CreateEnum
CREATE TYPE "CommitType" AS ENUM ('FEATURE', 'BUGFIX', 'HOTFIX', 'REFACTOR', 'DOCS', 'STYLE', 'TEST', 'CHORE', 'MERGE', 'REVERT', 'INITIAL');

-- CreateEnum
CREATE TYPE "ContributionType" AS ENUM ('OWNER', 'MAINTAINER', 'CONTRIBUTOR', 'COLLABORATOR', 'OCCASIONAL');

-- CreateTable
CREATE TABLE "repositories" (
    "id" SERIAL NOT NULL,
    "githubId" INTEGER NOT NULL,
    "name" TEXT NOT NULL,
    "fullName" TEXT NOT NULL,
    "owner" TEXT NOT NULL,
    "description" TEXT,
    "private" BOOLEAN NOT NULL DEFAULT false,
    "fork" BOOLEAN NOT NULL DEFAULT false,
    "htmlUrl" TEXT NOT NULL,
    "cloneUrl" TEXT NOT NULL,
    "gitUrl" TEXT NOT NULL,
    "sshUrl" TEXT NOT NULL,
    "homepage" TEXT,
    "stargazersCount" INTEGER NOT NULL DEFAULT 0,
    "watchersCount" INTEGER NOT NULL DEFAULT 0,
    "forksCount" INTEGER NOT NULL DEFAULT 0,
    "openIssuesCount" INTEGER NOT NULL DEFAULT 0,
    "subscribersCount" INTEGER NOT NULL DEFAULT 0,
    "networkCount" INTEGER NOT NULL DEFAULT 0,
    "size" INTEGER NOT NULL DEFAULT 0,
    "defaultBranch" TEXT NOT NULL DEFAULT 'main',
    "language" TEXT,
    "topics" TEXT[],
    "license" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL,
    "updatedAt" TIMESTAMP(3) NOT NULL,
    "pushedAt" TIMESTAMP(3),
    "projectType" "ProjectType" NOT NULL,
    "ecoSystemRole" TEXT,
    "dependencies" TEXT[],
    "technicalStack" TEXT[],
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "hasTests" BOOLEAN NOT NULL DEFAULT false,
    "hasDocumentation" BOOLEAN NOT NULL DEFAULT false,
    "hasCI" BOOLEAN NOT NULL DEFAULT false,
    "lastAnalysisAt" TIMESTAMP(3),
    "healthScore" DOUBLE PRECISION,
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "repositories_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "issues" (
    "id" SERIAL NOT NULL,
    "githubId" INTEGER NOT NULL,
    "number" INTEGER NOT NULL,
    "title" TEXT NOT NULL,
    "body" TEXT,
    "state" "IssueState" NOT NULL,
    "repositoryId" INTEGER NOT NULL,
    "authorLogin" TEXT NOT NULL,
    "authorAvatarUrl" TEXT,
    "authorType" TEXT,
    "assigneeLogin" TEXT,
    "assigneeAvatarUrl" TEXT,
    "labels" TEXT[],
    "milestone" TEXT,
    "locked" BOOLEAN NOT NULL DEFAULT false,
    "htmlUrl" TEXT NOT NULL,
    "apiUrl" TEXT NOT NULL,
    "priority" "IssuePriority",
    "category" TEXT,
    "estimatedHours" DOUBLE PRECISION,
    "isEcosystemIssue" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL,
    "updatedAt" TIMESTAMP(3) NOT NULL,
    "closedAt" TIMESTAMP(3),
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "issues_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "pull_requests" (
    "id" SERIAL NOT NULL,
    "githubId" INTEGER NOT NULL,
    "number" INTEGER NOT NULL,
    "title" TEXT NOT NULL,
    "body" TEXT,
    "state" "PullRequestState" NOT NULL,
    "repositoryId" INTEGER NOT NULL,
    "headBranch" TEXT NOT NULL,
    "baseBranch" TEXT NOT NULL,
    "headSha" TEXT NOT NULL,
    "baseSha" TEXT NOT NULL,
    "authorLogin" TEXT NOT NULL,
    "authorAvatarUrl" TEXT,
    "authorType" TEXT,
    "draft" BOOLEAN NOT NULL DEFAULT false,
    "mergeable" BOOLEAN,
    "mergeableState" TEXT,
    "merged" BOOLEAN NOT NULL DEFAULT false,
    "mergedBy" TEXT,
    "mergeCommitSha" TEXT,
    "additions" INTEGER NOT NULL DEFAULT 0,
    "deletions" INTEGER NOT NULL DEFAULT 0,
    "changedFiles" INTEGER NOT NULL DEFAULT 0,
    "commits" INTEGER NOT NULL DEFAULT 0,
    "htmlUrl" TEXT NOT NULL,
    "diffUrl" TEXT NOT NULL,
    "patchUrl" TEXT NOT NULL,
    "complexity" "PullRequestComplexity",
    "impactLevel" TEXT,
    "reviewStatus" TEXT,
    "isFeature" BOOLEAN NOT NULL DEFAULT false,
    "isBugfix" BOOLEAN NOT NULL DEFAULT false,
    "isHotfix" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL,
    "updatedAt" TIMESTAMP(3) NOT NULL,
    "closedAt" TIMESTAMP(3),
    "mergedAt" TIMESTAMP(3),
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "pull_requests_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "commits" (
    "id" SERIAL NOT NULL,
    "sha" TEXT NOT NULL,
    "message" TEXT NOT NULL,
    "repositoryId" INTEGER NOT NULL,
    "authorName" TEXT NOT NULL,
    "authorEmail" TEXT NOT NULL,
    "authorLogin" TEXT,
    "authorAvatarUrl" TEXT,
    "committerName" TEXT NOT NULL,
    "committerEmail" TEXT NOT NULL,
    "committerLogin" TEXT,
    "treeId" TEXT NOT NULL,
    "parentShas" TEXT[],
    "additions" INTEGER NOT NULL DEFAULT 0,
    "deletions" INTEGER NOT NULL DEFAULT 0,
    "total" INTEGER NOT NULL DEFAULT 0,
    "filesChanged" TEXT[],
    "htmlUrl" TEXT NOT NULL,
    "apiUrl" TEXT NOT NULL,
    "commitType" "CommitType",
    "impactScope" TEXT,
    "isBreaking" BOOLEAN NOT NULL DEFAULT false,
    "isMergeCommit" BOOLEAN NOT NULL DEFAULT false,
    "isRevert" BOOLEAN NOT NULL DEFAULT false,
    "authorDate" TIMESTAMP(3) NOT NULL,
    "committerDate" TIMESTAMP(3) NOT NULL,
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "commits_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "contributors" (
    "id" SERIAL NOT NULL,
    "githubId" INTEGER NOT NULL,
    "login" TEXT NOT NULL,
    "name" TEXT,
    "email" TEXT,
    "avatarUrl" TEXT,
    "gravatarId" TEXT,
    "bio" TEXT,
    "company" TEXT,
    "location" TEXT,
    "blog" TEXT,
    "twitterUsername" TEXT,
    "htmlUrl" TEXT NOT NULL,
    "followersUrl" TEXT NOT NULL,
    "followingUrl" TEXT NOT NULL,
    "reposUrl" TEXT NOT NULL,
    "publicRepos" INTEGER NOT NULL DEFAULT 0,
    "publicGists" INTEGER NOT NULL DEFAULT 0,
    "followers" INTEGER NOT NULL DEFAULT 0,
    "following" INTEGER NOT NULL DEFAULT 0,
    "ecoSystemRole" TEXT,
    "expertiseAreas" TEXT[],
    "mainLanguages" TEXT[],
    "isCore" BOOLEAN NOT NULL DEFAULT false,
    "githubCreatedAt" TIMESTAMP(3),
    "githubUpdatedAt" TIMESTAMP(3),
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "contributors_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "repository_contributors" (
    "id" SERIAL NOT NULL,
    "repositoryId" INTEGER NOT NULL,
    "contributorId" INTEGER NOT NULL,
    "contributions" INTEGER NOT NULL DEFAULT 0,
    "commitsCount" INTEGER NOT NULL DEFAULT 0,
    "issuesCount" INTEGER NOT NULL DEFAULT 0,
    "pullRequestsCount" INTEGER NOT NULL DEFAULT 0,
    "contributionType" "ContributionType" NOT NULL,
    "isOwner" BOOLEAN NOT NULL DEFAULT false,
    "isMaintainer" BOOLEAN NOT NULL DEFAULT false,
    "firstContribution" TIMESTAMP(3),
    "lastContribution" TIMESTAMP(3),
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "repository_contributors_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "readme_contents" (
    "id" SERIAL NOT NULL,
    "repositoryId" INTEGER NOT NULL,
    "content" TEXT NOT NULL,
    "contentMd" TEXT,
    "htmlUrl" TEXT NOT NULL,
    "fileName" TEXT NOT NULL DEFAULT 'README.md',
    "filePath" TEXT NOT NULL DEFAULT '/README.md',
    "fileSize" INTEGER NOT NULL DEFAULT 0,
    "fileSha" TEXT NOT NULL,
    "hasInstallation" BOOLEAN NOT NULL DEFAULT false,
    "hasUsage" BOOLEAN NOT NULL DEFAULT false,
    "hasExamples" BOOLEAN NOT NULL DEFAULT false,
    "hasAPI" BOOLEAN NOT NULL DEFAULT false,
    "hasContributing" BOOLEAN NOT NULL DEFAULT false,
    "hasLicense" BOOLEAN NOT NULL DEFAULT false,
    "hasBadges" BOOLEAN NOT NULL DEFAULT false,
    "wordCount" INTEGER NOT NULL DEFAULT 0,
    "readabilityScore" DOUBLE PRECISION,
    "completenessScore" DOUBLE PRECISION,
    "externalLinks" TEXT[],
    "internalLinks" TEXT[],
    "lastModified" TIMESTAMP(3) NOT NULL,
    "syncedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAtLocal" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAtLocal" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "readme_contents_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "repositories_githubId_key" ON "repositories"("githubId");

-- CreateIndex
CREATE UNIQUE INDEX "repositories_fullName_key" ON "repositories"("fullName");

-- CreateIndex
CREATE UNIQUE INDEX "issues_githubId_key" ON "issues"("githubId");

-- CreateIndex
CREATE UNIQUE INDEX "issues_repositoryId_number_key" ON "issues"("repositoryId", "number");

-- CreateIndex
CREATE UNIQUE INDEX "pull_requests_githubId_key" ON "pull_requests"("githubId");

-- CreateIndex
CREATE UNIQUE INDEX "pull_requests_repositoryId_number_key" ON "pull_requests"("repositoryId", "number");

-- CreateIndex
CREATE UNIQUE INDEX "commits_sha_key" ON "commits"("sha");

-- CreateIndex
CREATE UNIQUE INDEX "contributors_githubId_key" ON "contributors"("githubId");

-- CreateIndex
CREATE UNIQUE INDEX "contributors_login_key" ON "contributors"("login");

-- CreateIndex
CREATE UNIQUE INDEX "repository_contributors_repositoryId_contributorId_key" ON "repository_contributors"("repositoryId", "contributorId");

-- CreateIndex
CREATE UNIQUE INDEX "readme_contents_repositoryId_key" ON "readme_contents"("repositoryId");

-- AddForeignKey
ALTER TABLE "issues" ADD CONSTRAINT "issues_repositoryId_fkey" FOREIGN KEY ("repositoryId") REFERENCES "repositories"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "pull_requests" ADD CONSTRAINT "pull_requests_repositoryId_fkey" FOREIGN KEY ("repositoryId") REFERENCES "repositories"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "commits" ADD CONSTRAINT "commits_repositoryId_fkey" FOREIGN KEY ("repositoryId") REFERENCES "repositories"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "repository_contributors" ADD CONSTRAINT "repository_contributors_repositoryId_fkey" FOREIGN KEY ("repositoryId") REFERENCES "repositories"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "repository_contributors" ADD CONSTRAINT "repository_contributors_contributorId_fkey" FOREIGN KEY ("contributorId") REFERENCES "contributors"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "readme_contents" ADD CONSTRAINT "readme_contents_repositoryId_fkey" FOREIGN KEY ("repositoryId") REFERENCES "repositories"("id") ON DELETE CASCADE ON UPDATE CASCADE;
