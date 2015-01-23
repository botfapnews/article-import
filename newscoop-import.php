<?php

require_once dirname(__FILE__) . '/newscoop_bootstrap.php';

// Article definition

$publication_id = 1;
$issue_number = 1;
$section_number = 1;

$article_language = 1;
$article_title = 'Article title text';
$article_creation_date = '2015-01-20 00:00:00';
$article_publish_date = '2015-01-20 00:00:00';
$article_published = 'Y';  // N - New
$article_author = 'John Smith';
$article_author_email = 'john@smith.name';
$article_author_type = 1; // Author

$article_type = 'news'; // Define your own type
$article = array(
    'FLead' => 'lead text',
    'FBody' => '<p>Body text</p>',
);

// End article definition

if ($publication_id > 0) {
    $publicationObj = new Publication($publication_id);
    if (!$publicationObj->exists()) {
        die("Publication does not exist.\n");
    }

    if ($issue_number > 0) {
        $issueObj = new Issue($publication_id, $article_language, $issue_number);
        if (!$issueObj->exists()) {
            die("Issue does not exist.\n");
        }

        if ($section_number > 0) {
            $sectionObj = new Section($publication_id, $issue_number, $article_language, $section_number);
            if (!$sectionObj->exists()) {
                die("Section does not exist.\n");
            }
        }
    }
}

$articles = Article::GetByName($article_title, $publication_id, $issue_number, $section_number, null, true);
if (count($articles) == 0) {
    $action = 'created';
    $articleObj = new Article($article_language);
    $articleObj->create($article_type, $article_title, $publication_id, $issue_number, $section_number);
} else {
    $action = 'updated';
    $articleObj = $articles[0];
}

if ($articleObj->exists()) {
    $authorObj = new Author($article_author);
    if (!$authorObj->exists()) {
        $authorData = Author::ReadName($article_author);
        $authorData['email'] = $article_author_email;
        $authorObj->create($authorData);
    }
    $articleObj->setAuthor($authorObj);

    $articleObj->setIsPublic(true);
    if ($publication_id > 0) {
        $commentDefault = $publicationObj->commentsArticleDefaultEnabled();
        $articleObj->setCommentsEnabled($commentDefault);
    }

    $articleTypeObj = $articleObj->getArticleData();
    $dbColumns = $articleTypeObj->getUserDefinedColumns(false, true);
    foreach ($dbColumns as $dbColumn) {
        if (!empty($article[$dbColumn->getName()])) {
            $articleTypeObj->setProperty($dbColumn->getName(), $article[$dbColumn->getName()]);
        }
    }

    $articleObj->setProperty('time_updated', 'NOW()', true, true);
    $articleObj->setCreationDate($article_creation_date);
    $articleObj->setPublishDate($article_publish_date);
    $articleObj->setProperty('Published', $article_published);

    ArticleIndex::RunIndexer(3, 10, true);
    echo "Article '{$article_title}' {$action}.\n";
} else {
    die("Could not create article.\n");
}
