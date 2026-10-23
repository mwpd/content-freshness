<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class BrokenLifecyclePlugin extends ServiceBasedPlugin {

	protected function get_service_classes(): array {
		return [
			'RepeatingRegistration'        => RepeatingRegistration::class,
			'ChangingPriorityRegistration' => ChangingPriorityRegistration::class,
			'AppendingActivation'          => AppendingActivation::class,
			'ChangingDeactivation'         => ChangingDeactivation::class,
			'ChangingTransient'            => ChangingTransient::class,
			'ReschedulingActivation'       => ReschedulingActivation::class,
			'ChangingArgumentsScheduling'  => ChangingArgumentsScheduling::class,
			'AppendingScopedLifecycle'     => AppendingScopedLifecycle::class,
			'ThrowingLifecycle'            => ThrowingLifecycle::class,
		];
	}
}
