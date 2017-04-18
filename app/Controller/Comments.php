<?php

namespace App\Controller;

use App\Router\AbstractRouter;
use App\Database;
use App\Storage\Comments as CommentStorage;
use App\Storage\Commenters as CommenterStorage;


class Comments extends AbstractRouter
{
    /**
     * The database
     * @var Database
     */
    protected $db;

    /**
     * Constructs the blog controller with a Database object
     *
     * @param Database $db The database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Handles a URL
     *
     * @param string $url
     * @return array The route match
     */
	protected function matchAgainst($url)
	{
		/*
		POST /article-slug/comments
		GET  /article-slug/comments?action=confirm&email=user@domain.com&key=38297493...&comment=42
		GET  /commenter/user@domain.com
		*/

		$match = [];

		$article = $this->request->getAttribute('article_id');
		if ($article) {
			if ($this->request->getMethod == 'POST') {
				$response = $this->postComment();
			} else {
				$response = $this->confirmComment();
			}
		} else {
			$chunks = $this->chunkify($url);
			$email = null;
			foreach ($chunks as $chunk) {
				if (preg_match('/^[^@]+@([^\.]+\.)+[a-z]{2,10}$/', $chunk)) {
					$email = $chunk;
				}
			}
			$match['response'] = $this->getCommenterOption($email);
		}

		return $match;
	}

	/**
	 * Attempts to post a comment
	 *
	 * @return ResponseInterface The response
	 */
	protected function postComment()
	{
		$article = $this->request->getAttribute('article_id');

		$commenterStorage = new CommenterStorage($this->db);

		$result = [ 'errors' => [] ];
		$values = $this->request->getPost();

		if (isset($values['email'])) {
			$commenter = $commenterStorage->findByEmail($values['email']);
			if (!$commenter) {
				if (!$values['name']) {
					$result['errors']['name'] = 'Please enter a name';
				} else {
					$commenter = $commenterStorage->createWithEmail($values['email'], $values['name']);
				}
			}

			if ($commenter) {
				$result['commenter_key'] = $commenter['id'];
			}
		} else if (isset($values['commenter_key'])) {
			$commenter = $commenterStorage->findById($values['commenter_key']);
		} else {
			$result['errors']['email'] = 'Please enter an e-mail address';
		}

		if ($commenter) {
			if ($commenter['is_active'] == 0) {
				$result['errors']['commenter_key'] = 'Your e-mail address has been blocked by the administrator';
			} else {
				if (!$values['comment']) {
					$result['errors']['comment'] = 'The comment cannot be empty';
				} else {
					$commentStorage = new CommentStorage($this->db);

					$data = [
						'article_id' => $article['id'],
						'in_reply_to' => $values['in_reply_to'],
						'commenter_id' => $commenter['id'],
						'ip' => $_SERVER['REMOTE_ADDR'],
						'comment' => $values['comment']
					];

					$knownIps = $commentStorage->getIpListByCommenter($commenter['id']);
					if (!in_array($_SERVER['REMOTE_ADDR'], $knownIps)) {
						$data['confirm_key'] = md5(time() . $commenter['email']);
						$data['is_visible'] = 0;

					}

					$id = $commentStorage->insert($data);
					if ($data['is_visible']) {
						$result['comment'] = $commentStorage->findById($id);
						sendEmail('confirm_comment', $ommenter['name'], $commenter['email'], [ 'url' => '/' . $artcile['slug'] . '/comments?action=confirm&email=' . $commenter['email'] . '&key=' . $data['confirm_key'] . '&comment=' . $id ]);
					} else {
						$result['message'] = [ 'type' => 'info', 'message' => 'We have send you an e-mail with a link to confirm your comment. Please check your mailbox and click on the link to confirm that it was you who commented.' ];
					}
				}
			}
		}

		$response = new JsonResponse();
		$response->getBody()->write($result);

		return $response;
	}

	/**
	 * Attempts to confirm a comment
	 *
	 * @return ResponseInterface The response
	 */
	protected function confirmComment()
	{
		$result = [ 'errors' => [] ];
		$values = $this->request->getQueryParams();

		$commenterStorage = new CommenterStorage($this->db);
		$commenter = $commenterStorage->findByEmail($values['email']);

		if (!$commenter) {
			$result['errors']['email'] = 'Invalid e-mail address';
		} else {
			$commentStorage = new CommentStorage($this->db);

			$comment = $commentStorage->findById($values['comment']);
			if (!$comment || $comment['commenter_id'] != $commenter['id'] || $comment['confirm_key'] != $values['key']) {
				$result['errors']['comment'] = 'Invalid comment';
			} else {
				$update = [ 'is_visible' => 1, 'confirm_key' => null ];
				$commentStorage->update($update, $comment['id']);

				$article = $articleStorage->findById($comment['article_id']);
				header('Location: /' . $article['slug'] . '#comments');
				exit;
			}
		}

		$response = new JsonResponse();
		$response->getBody()->write($result);

		return $response;
	}

	/**
	 * Tries to find a commenter by e-mail address
	 *
	 * @return ResponseInterface The response
	 */
	protected function getCommenterOption($email)
	{
		$commenterStorage = new CommenterStorage($this->db);

		if ($email) {
			$commenter = $commenterStorage->findByEmail($email);
		}

		$result = [
			'found' => $commenter ? true : false,
			'name' => $commenter['name'],
			'key' => $commenter['id']
		];

		$response = new JsonResponse();
		$response->getBody()->write($result);

		return $response;
	}

}
