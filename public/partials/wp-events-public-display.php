<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://prolificdigital.com
 * @since      1.0.0
 *
 * @package    Wp_Events
 * @subpackage Wp_Events/public/partials
 */
?>

<div class="block event">
  <div class="wrapper">
    <div class="row">
      <div class="col">
        <p class="date">Date: <?php the_field('start_date'); ?></p>

        <?php if (get_field('end_time')) : ?>
          <p class="time"><span>Time: <?php the_field('start_time'); ?></span> - <span><?php the_field('end_time'); ?></span></p>
        <?php else : ?>
          <p class="time"><span>Time: <?php the_field('start_time'); ?></span></p>
        <?php endif; ?>

        <?php if (get_field('series_repeat')) : ?>
          <p class="repeats">Repeats: <?php the_field('series_repeat'); ?></p>
        <?php endif; ?>

        <?php if (get_field('zoom_url')) : ?>
          <a href="<?php the_field('zoom_url'); ?>" class="btn" target="_blank">Zoom Link</a>
        <?php endif; ?>

        <?php if (get_field('registration_link')) : ?>
          <a href="<?php the_field('registration_link'); ?>" class="btn" target="_blank">Registration Link</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>