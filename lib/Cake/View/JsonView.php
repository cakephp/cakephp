<?php

class JsonView extends View {

/**
 * Render a JSON view.
 *
 * Uses the special 'serialize' parameter to convert a set of
 * view variables into a JSON response.  Makes generating simple 
 * JSON responses very easy.  You can omit the 'serialize' parameter, 
 * and use a normal view + layout as well.
 *
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return The rendered view.
 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['serialize'])) {
			$vars = array_intersect_key(
				$this->viewVars,
				array_flip($this->viewVars['serialize'])
			);
			return json_encode($vars);
		}
		return parent::render($view, $layout);
	}
}
