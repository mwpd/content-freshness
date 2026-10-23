<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NamesWithoutTypeKind\Excluded;

interface FooInterface {
}

namespace mwpd\contentfreshness\tests\phpstan\fixture\nameswithouttypekind\excluded;

trait FooTrait {
}

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NamesWithoutTypeKindSibling;

final class FooImpl {
}
