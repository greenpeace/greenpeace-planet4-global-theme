<?php
/**
 * Highlighted CTA pattern class.
 *
 * @package P4GBKS
 * @since 0.1
 */

namespace P4GBKS\Patterns;

/**
 * Class Highlighted CTA.
 *
 * @package P4GBKS\Patterns
 */
class HighlightedCta extends Block_Pattern {

	/**
	 * Returns the pattern name.
	 */
	public static function get_name(): string {
		return 'p4/highlighted-cta';
	}

	/**
	 * Returns the pattern config.
	 */
	public static function get_config(): array {
		return [
			'title'      => __( 'Highlighted CTA', 'planet4-blocks-backend' ),
			'categories' => [ 'planet4' ],
			'content'    => '
				<!-- wp:columns {"className":"block","textColor":"white","backgroundColor":"dark-green"} -->
					<div class="wp-block-columns block has-dark-green-background-color has-text-color has-background has-white-color">
						<!-- wp:column -->
							<div class="wp-block-column">
								<!-- wp:image {"align":"center"} -->
									<div class="wp-block-image">
										<figure class="aligncenter">
											<img alt=""/>
										</figure>
									</div>
								<!-- /wp:image -->
								<!-- wp:heading {"placeholder":"' . __( 'Enter text', 'planet4-blocks-backend' ) . '","align":"center","className":"has-text-align-center","level":3} -->
									<h3 class="has-text-align-center"></h3>
								<!-- /wp:heading -->
								<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
								<!-- /wp:spacer -->
								<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
									<div class="wp-block-buttons">
										<!-- wp:button {"className":"transparent-button"} /-->
									</div>
								<!-- /wp:buttons -->
								<!-- wp:spacer {"height":"16px"} -->
									<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
								<!-- /wp:spacer -->
							</div>
						<!-- /wp:column -->
					</div>
				<!-- /wp:columns -->
			',
		];
	}
}
