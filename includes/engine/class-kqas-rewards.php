<?php
/**
 * Rewards engine (MVP).
 *
 * - 4–6: Stickers
 * - 7–12: Badges
 *
 * في MVP: المكافأة = "slug + title" (والـUI يقرر كيف يعرضها).
 * لاحقاً يمكن ربطها بملفات assets/media/stickers أو badges.
 *
 * يعتمد على:
 * - إعدادات عامة: kqas_reward_4_6, kqas_reward_7_12 (من Settings/Activator)
 * - Meta quiz: _kqas_reward_mode (من Metaboxes)
 *
 * @package KidQuiz_Age_Smart
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'KQAS_Rewards', false ) ) :

	final class KQAS_Rewards {

		/**
		 * Get reward mode for age group (default from settings).
		 *
		 * @param string $age_group Age group.
		 * @return string 'stickers'|'badges'
		 */
		public static function get_default_reward_mode_for_age_group( $age_group ) {
			$age_group = class_exists( 'KQAS_Age_Rules' ) ? KQAS_Age_Rules::normalize_age_group( $age_group ) : '4-6';

			$get = function( $option, $default ) {
				if ( class_exists( 'KQAS_Settings' ) && is_callable( array( 'KQAS_Settings', 'get' ) ) ) {
					return KQAS_Settings::get( $option, $default );
				}
				return get_option( $option, $default );
			};

			if ( '4-6' === $age_group ) {
				$mode = sanitize_text_field( (string) $get( 'kqas_reward_4_6', 'stickers' ) );
			} else {
				$mode = sanitize_text_field( (string) $get( 'kqas_reward_7_12', 'badges' ) );
			}

			return in_array( $mode, array( 'stickers', 'badges' ), true ) ? $mode : 'stickers';
		}

		/**
		 * Choose a reward item for session end.
		 *
		 * @param string $reward_mode 'stickers'|'badges'
		 * @param string $age_group   Age group for flavor (optional).
		 * @param array  $context     Optional info: score, streak, etc.
		 * @return array{mode:string,slug:string,title:string,asset_url:string}
		 */
		public static function pick_reward( $reward_mode, $age_group = '4-6', $context = array() ) {
			$reward_mode = sanitize_text_field( (string) $reward_mode );
			if ( ! in_array( $reward_mode, array( 'stickers', 'badges' ), true ) ) {
				$reward_mode = self::get_default_reward_mode_for_age_group( $age_group );
			}

			$pool = ( 'stickers' === $reward_mode ) ? self::stickers_pool() : self::badges_pool();

			// Very simple: random pick, later could be based on performance.
			$item = $pool[ array_rand( $pool ) ];

			$asset_url = self::resolve_asset_url( $reward_mode, $item['slug'] );

			return array(
				'mode'      => $reward_mode,
				'slug'      => $item['slug'],
				'title'     => $item['title'],
				'asset_url' => $asset_url,
			);
		}

		/**
		 * Get a list of sticker items.
		 *
		 * @return array<int,array{slug:string,title:string}>
		 */
		private static function stickers_pool() {
			return array(
				array( 'slug' => 'star',        'title' => esc_html__( 'Shiny Star', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'smile',       'title' => esc_html__( 'Happy Smile', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'rocket',      'title' => esc_html__( 'Little Rocket', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'unicorn',     'title' => esc_html__( 'Cute Unicorn', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'rainbow',     'title' => esc_html__( 'Rainbow', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'trophy-mini', 'title' => esc_html__( 'Mini Trophy', 'kidquiz-age-smart' ) ),
			);
		}

		/**
		 * Get a list of badge items.
		 *
		 * @return array<int,array{slug:string,title:string}>
		 */
		private static function badges_pool() {
			return array(
				array( 'slug' => 'fast-learner',   'title' => esc_html__( 'Fast Learner', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'quiz-champion',  'title' => esc_html__( 'Quiz Champion', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'smart-streak',   'title' => esc_html__( 'Smart Streak', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'focus-master',   'title' => esc_html__( 'Focus Master', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'level-up',       'title' => esc_html__( 'Level Up', 'kidquiz-age-smart' ) ),
				array( 'slug' => 'great-effort',   'title' => esc_html__( 'Great Effort', 'kidquiz-age-smart' ) ),
			);
		}

		/**
		 * Resolve reward asset URL from plugin assets (optional).
		 *
		 * Convention:
		 * - assets/media/stickers/{slug}.png
		 * - assets/media/badges/{slug}.png
		 *
		 * If file doesn't exist, return empty string (UI can fallback).
		 *
		 * @param string $reward_mode stickers|badges
		 * @param string $slug        item slug
		 * @return string
		 */
		private static function resolve_asset_url( $reward_mode, $slug ) {
			$reward_mode = ( 'badges' === $reward_mode ) ? 'badges' : 'stickers';
			$slug        = sanitize_title( $slug );

			$relative = 'assets/media/' . $reward_mode . '/' . $slug . '.png';
			$path     = trailingslashit( KQAS_PLUGIN_DIR ) . $relative;

			if ( file_exists( $path ) ) {
				return trailingslashit( KQAS_PLUGIN_URL ) . $relative;
			}

			return '';
		}
	}

endif;
