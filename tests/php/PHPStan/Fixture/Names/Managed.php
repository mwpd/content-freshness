<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Names;

final class Managed {

	public function work(): void {
		get_option( 'uninstall_plugins' );
		update_option( 'uninstall_plugins', [] );
		delete_option( 'uninstall_plugins' );
		update_option( 'active_plugins', [] );
	}
}
