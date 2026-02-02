<?php
/**
 * Register all actions and filters for the plugin.
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Loader', false ) ) :

	/**
	 * Orchestrates WordPress hooks (actions/filters) in a clean way.
	 */
	final class KQAS_Loader {

		/**
		 * The array of actions registered with WordPress.
		 *
		 * @var array<int, array<string, mixed>>
		 */
		protected $actions = array();

		/**
		 * The array of filters registered with WordPress.
		 *
		 * @var array<int, array<string, mixed>>
		 */
		protected $filters = array();

		/**
		 * Add a new action to the collection.
		 *
		 * @param string   $hook          The name of the WordPress action.
		 * @param object   $component     A reference to the instance of the object on which the action is defined.
		 * @param string   $callback      The name of the function on the $component.
		 * @param int      $priority      Optional. The priority at which the function should be fired. Default 10.
		 * @param int      $accepted_args Optional. The number of arguments that should be passed to the callback. Default 1.
		 *
		 * @return void
		 */
		public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Add a new filter to the collection.
		 *
		 * @param string   $hook          The name of the WordPress filter.
		 * @param object   $component     A reference to the instance of the object on which the filter is defined.
		 * @param string   $callback      The name of the function on the $component.
		 * @param int      $priority      Optional. The priority at which the function should be fired. Default 10.
		 * @param int      $accepted_args Optional. The number of arguments that should be passed to the callback. Default 1.
		 *
		 * @return void
		 */
		public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * A utility function that is used to register the actions and hooks into a single collection.
		 *
		 * @param array<int, array<string, mixed>> $hooks         The collection of hooks that is being registered (that is, actions or filters).
		 * @param string                          $hook          The name of the WordPress filter that is being registered.
		 * @param object                          $component     A reference to the instance of the object on which the filter is defined.
		 * @param string                          $callback      The name of the function on the $component.
		 * @param int                             $priority      The priority at which the function should be fired.
		 * @param int                             $accepted_args The number of arguments that should be passed to the callback.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
			$hooks[] = array(
				'hook'          => $hook,
				'component'     => $component,
				'callback'      => $callback,
				'priority'      => (int) $priority,
				'accepted_args' => (int) $accepted_args,
			);

			return $hooks;
		}

		/**
		 * Register the filters and actions with WordPress.
		 *
		 * @return void
		 */
		public function run() {
			foreach ( $this->filters as $hook ) {
				add_filter(
					$hook['hook'],
					array( $hook['component'], $hook['callback'] ),
					$hook['priority'],
					$hook['accepted_args']
				);
			}

			foreach ( $this->actions as $hook ) {
				add_action(
					$hook['hook'],
					array( $hook['component'], $hook['callback'] ),
					$hook['priority'],
					$hook['accepted_args']
				);
			}
		}
	}

endif;
