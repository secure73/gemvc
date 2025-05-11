<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of a complete database workflow using QueryBuilder
$queryBuilder = new QueryBuilder();

// Step 1: Create a new posts table (this would normally be in migrations)
echo "WORKFLOW EXAMPLE: Blog Post Management\n";
echo "------------------------------------\n\n";

// Step 2: Insert a new post
$insert = $queryBuilder->insert('posts')
    ->columns('title', 'content', 'author_id', 'created_at')
    ->values(
        'Getting Started with Gemvc QueryBuilder', 
        'This is a sample post about using the QueryBuilder in Gemvc...',
        1,
        date('Y-m-d H:i:s')
    );

echo "Creating new post...\n";
echo "SQL: " . $insert . "\n";

$postId = $insert->run();
if ($postId === false) {
    echo "Error creating post: " . $insert->getError() . "\n";
    exit;
}
echo "Post created with ID: $postId\n\n";

// Step 3: Insert tags for the post
$tags = ['php', 'database', 'gemvc', 'tutorial'];
echo "Adding tags to post...\n";

foreach ($tags as $tag) {
    $tagInsert = $queryBuilder->insert('post_tags')
        ->columns('post_id', 'tag_name')
        ->values($postId, $tag);
    
    $tagResult = $tagInsert->run();
    if ($tagResult === false) {
        echo "Error adding tag '$tag': " . $tagInsert->getError() . "\n";
    } else {
        echo "Added tag '$tag'\n";
    }
}
echo "\n";

// Step 4: Update the post
echo "Updating post...\n";
$update = $queryBuilder->update('posts')
    ->set('title', 'Complete Guide to Gemvc QueryBuilder')
    ->set('updated_at', date('Y-m-d H:i:s'))
    ->where('id', '=', $postId);

echo "SQL: " . $update . "\n";
$updateResult = $update->run();

if ($updateResult === false) {
    echo "Error updating post: " . $update->getError() . "\n";
} else {
    echo "Post updated successfully\n\n";
}

// Step 5: Query the post with its tags
echo "Retrieving post with tags...\n";
$select = $queryBuilder->select('p.*', 'GROUP_CONCAT(t.tag_name) as tags')
    ->from('posts', 'p')
    ->leftJoin('post_tags t ON p.id = t.post_id')
    ->where('p.id', '=', $postId)
    ->orderBy('p.created_at', true);

echo "SQL: " . $select . "\n";
$selectResult = $select->run();

if ($selectResult === false) {
    echo "Error retrieving post: " . $select->getError() . "\n";
} else {
    $post = $selectResult[0] ?? null;
    if ($post) {
        echo "Retrieved post: \n";
        echo "- Title: {$post['title']}\n";
        echo "- Content: " . substr($post['content'], 0, 50) . "...\n";
        echo "- Tags: {$post['tags']}\n";
        echo "- Created: {$post['created_at']}\n\n";
    } else {
        echo "Post not found\n\n";
    }
}

// Step 6: Delete the post (cleanup)
echo "Deleting post and tags...\n";

// First delete the tags
$deleteFlags = $queryBuilder->delete('post_tags')
    ->where('post_id', '=', $postId)
    ->run();

if ($deleteFlags === false) {
    echo "Error deleting tags: " . $queryBuilder->getError() . "\n";
} else {
    echo "Deleted $deleteFlags tags\n";
}

// Then delete the post
$deletePost = $queryBuilder->delete('posts')
    ->where('id', '=', $postId)
    ->run();

if ($deletePost === false) {
    echo "Error deleting post: " . $queryBuilder->getError() . "\n";
} else {
    echo "Deleted post successfully\n";
}

echo "\nWorkflow complete!\n"; 