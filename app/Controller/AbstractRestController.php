<?php

namespace App\Controller;

use App\Router\AbstractRouter;

/*
GET    /article-slug/comments
GET    /article-slug/comments/:id
POST   /article-slug/comments
POST   /article-slug/comments/:id
DELETE /article-slug/comments/:id

GET    /admin/blogs
GET    /admin/blogs?mode=add
GET    /admin/blogs/:id
GET    /admin/blogs/:id?mode=edit
POST   /admin/blogs[?mode=add]
POST   /admin/blogs/:id[?mode=add]
DELETE /admin/blogs/:id
*/

class AbstractRestController extends AbstractRouter
{
	protected $templatePath = 'rest';

	protected function matchAgainst($url)
	{
		$method = strtolower($this->request->getMethod());
		$mode = $this->request->getQueryParam('mode');
		$chunks = $this->chunkify($url);

		if ($method == 'get') {
			if (count($chunks) > 1) {
				$actionPage = $mode == 'edit' ? 'edit' : 'show';
				$result = $this->fetchOne($chunks[1]);
			} else {
				$actionPage = $mode == 'add' ? 'add' : 'show';
				$result = $mode == 'add' ? [] : $this->fetchAll();
			}
		} else if ($method == 'post') {
			if (count($chunks) > 1) {
				$actionPage = 'edit';
				$result = $this->update($chunks[1], $this->request->getPost());
			} else {
				$actionPage = 'add';
				$result = $this->add($this->request->getPost());
			}
		} else if ($method == 'delete' && count($chunks) > 1) {
			$actionPage = 'delete';
			$result = $this->delete($chunks[1]);
		} else {
			throw new InvalidRequestException('Invalid REST request: ' . $this->request->getUri());
		}

		if ($this->request->getHeader('X-Requested-With') == 'XmlHttpRequest') {
			$this->response = new JsonResponse();
			$this->response->getBody()->write($result);
		} else if (isset($result['redirect'])) {
			$this->response = new RedirectResponse($result['redirect']);
		} else {
			$layout = new Template('layout/admin');
			$template = new Template($this->templatePath . '/' . $actionPage);
			$layout->content = $template->render($result);
			$this->response->getBody()->write($layout->render());
		}

		return [ 'response' => $response ];
	}

	abstract protected function fetchAll();
	abstract protected function fetchOne($id);
	abstract protected function update($id, $values);
	abstract protected function add($values);
	abstract protected function delete($id);
}