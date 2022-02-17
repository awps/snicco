<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use stdClass;

use function array_keys;

final class BetterWPDB_reads_Test extends WPTestCase
{

    private BetterWPDB $better_wpdb;

    protected function setUp(): void
    {
        $this->better_wpdb = BetterWPDB::fromWpdb();
        $this->better_wpdb->safeQuery('DROP TABLE IF EXISTS test_table', []);
        $this->better_wpdb->safeQuery(
            'CREATE TABLE IF NOT EXISTS `test_table` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `test_string` varchar(30) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  `test_float` FLOAT(9,2) UNSIGNED DEFAULT NULL,
  `test_int` INTEGER UNSIGNED DEFAULT NULL,
  `test_bool` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;', []
        );

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->safeQuery('DROP TABLE IF EXISTS test_table');
    }

    /**
     * @test
     */
    public function test_select(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string) values('foo')",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int) values('foobar', 1)",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_bool) values('baz', true)",
        );


        $stmt = $this->better_wpdb->select('select * from test_table where id = ?', [1]);
        $this->assertSame(1, $stmt->num_rows);

        $stmt = $this->better_wpdb->select('select * from test_table where test_string LIKE ?', ['foo%']);
        $this->assertSame(2, $stmt->num_rows);

        $stmt = $this->better_wpdb->select('select * from test_table where test_string = ?', ['bar']);
        $this->assertSame(0, $stmt->num_rows);

        $stmt = $this->better_wpdb->select('select * from test_table where test_bool = ?', [true]);
        $this->assertSame(1, $stmt->num_rows);

        $stmt = $this->better_wpdb->select('select * from test_table where test_bool = ?', [false]);
        $this->assertSame(2, $stmt->num_rows);

        $stmt = $this->better_wpdb->select('select * from test_table where test_float <=> ?', [null]);
        $this->assertSame(3, $stmt->num_rows);
    }

    /**
     * @test
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function test_selectAll(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int) values('foo', 1)",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int) values('bar', 2)",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_bool, test_float, test_int) values('baz', true, 20.20, 3)",
        );

        $all = $this->better_wpdb->selectAll('select * from test_table', []);
        $this->assertCount(3, $all);
        foreach ($all as $row) {
            $this->assertArrayHasKey('test_string', $row);
            $this->assertArrayHasKey('test_int', $row);
            $this->assertArrayHasKey('test_float', $row);
            $this->assertArrayHasKey('test_bool', $row);
        }

        $all = $this->better_wpdb->selectAll('select test_string from test_table where test_int < ?', [3]);
        $this->assertCount(2, $all);
        foreach ($all as $row) {
            $this->assertSame(['test_string'], array_keys($row));
        }
        $this->assertSame('foo', $all[0]['test_string']);
        $this->assertSame('bar', $all[1]['test_string']);
    }

    /**
     * @test
     */
    public function test_selectLazy(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('foo', 1, 10.00, true )",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('bar', 2, 20.00, true )",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('baz', 3, 30.00, true )",
        );

        $all = $this->better_wpdb->selectLazy('select * from test_table where test_int < ?', [3]);
        $this->assertInstanceOf(Generator::class, $all);

        $count = 0;

        foreach ($all as $row) {
            $this->assertIsArray($row);
            $this->assertIsInt($row['test_int']);
            $this->assertIsString($row['test_string']);
            $this->assertIsFloat($row['test_float']);
            $this->assertSame(1, $row['test_bool']);
            $count++;
        }

        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function test_selectRow(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('foo', 1, 10.00, true )",
        );

        $row = $this->better_wpdb->selectRow(
            'select * from test_table where test_int = ? and test_float = ?',
            [1, 10.00]
        );

        $this->assertSame('foo', $row['test_string']);
        $this->assertSame(1, $row['test_int']);
        $this->assertSame(10.00, $row['test_float']);
        $this->assertSame(1, $row['test_bool']);

        $this->expectException(NoMatchingRowFound::class);
        $this->better_wpdb->selectRow(
            'select * from test_table where test_int = ? and test_float = ?',
            [1, 20.00]
        );
    }

    /**
     * @test
     */
    public function test_selectRow_returns_first_row_for_multiple_matches(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('foo', 1, 10.00, true )",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('bar', 2, 20.00, true )",
        );

        $row = $this->better_wpdb->selectRow(
            'select * from test_table where test_float < ?',
            [30.00]
        );

        $this->assertSame('foo', $row['test_string']);
        $this->assertSame(1, $row['test_int']);
        $this->assertSame(10.00, $row['test_float']);
        $this->assertSame(1, $row['test_bool']);
    }

    /**
     * @test
     */
    public function test_selectValue(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('foo', 1, 10.00, true )",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('bar', 2, 20.00, true )",
        );

        /** @var int $value */
        $value = $this->better_wpdb->selectValue(
            'select count(test_float) from test_table where test_float < ?',
            [30.00]
        );
        $this->assertSame(2, $value);

        /** @var int $value */
        $value = $this->better_wpdb->selectValue(
            'select count(test_float) from test_table where test_float < ?',
            [5.00]
        );

        $this->assertSame(0, $value);
    }

    /**
     * @test
     */
    public function test_exists(): void
    {
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_bool) values('foo', 1, true )",
        );
        $this->better_wpdb->safeQuery(
            "insert into test_table (test_string, test_int, test_float, test_bool) values('bar', 2, 20.00, true )",
        );

        $this->assertTrue(
            $this->better_wpdb->exists('test_table', ['test_string' => 'foo', 'test_float' => null, 'test_int' => 1])
        );

        $this->assertFalse(
            $this->better_wpdb->exists('test_table', ['test_string' => 'bar', 'test_float' => null, 'test_int' => 2])
        );
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exists_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->exists('', ['test_string' => 'foo', 'test_float' => null, 'test_int' => 1]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exists_throws_exception_for_empty_string_condition_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->exists('test_table', ['' => 'foo', 'test_float' => null, 'test_int' => 1]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exists_throws_exception_for_non_string_condition_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->exists('test_table', ['foo', 'test_float' => null, 'test_int' => 1]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exists_throws_exception_for_non_scalar_condition_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->exists('test_table', ['test_int' => new stdClass()]);
    }

}