<?php

declare(strict_types=1);

use HarryM\DomainSupport\Exceptions\UnmappedCriteriaException;
use HarryM\DomainSupport\Repositories\AbstractRepository;
use HarryM\DomainSupport\Repositories\CriteriaInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// Test Model for testing purposes
class TestModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['name', 'email', 'status', 'customer_id'];

    /**
     * @return HasOne<TestModel, $this>
     */
    public function relation()
    {
        return $this->hasOne(TestModel::class, 'id', 'id');
    }

    /**
     * @return BelongsTo<TestCustomer, $this>
     */
    public function customer()
    {
        return $this->belongsTo(TestCustomer::class, 'customer_id');
    }
}

// Related model used to test searchableRelations()
class TestCustomer extends Model
{
    protected $table = 'test_customers';

    protected $fillable = ['name'];
}

// Test Repository implementation
class TestRepository extends AbstractRepository
{
    protected string $model = TestModel::class;

    protected ?array $sortableColumns = ['name', 'email', 'created_at'];

    protected ?array $searchableColumns = ['name', 'email'];

    protected ?array $with = ['relation'];
}

// Test Repository implementation searching a related model's columns
class TestRepositoryWithSearchableRelations extends AbstractRepository
{
    protected string $model = TestModel::class;

    protected ?array $searchableColumns = ['name'];

    protected array $searchableRelations = [
        'customer' => ['name'],
    ];
}

// Test Repository implementation searching only a related model's columns
class TestRepositoryWithOnlySearchableRelations extends AbstractRepository
{
    protected string $model = TestModel::class;

    protected ?array $searchableColumns = [];

    protected array $searchableRelations = [
        'customer' => ['name'],
    ];
}

// Fake Scout builder used to spy on the limit applied before ->keys() is called
class ScoutBuilderSpy
{
    public static ?int $capturedLimit = null;

    /** @var array<int,int> */
    public static array $resultKeys = [1, 2, 3];

    public function take(int $limit): static
    {
        self::$capturedLimit = $limit;

        return $this;
    }

    /**
     * @return SupportCollection<int,int>
     */
    public function keys(): SupportCollection
    {
        return collect(self::$resultKeys);
    }
}

// Fake model exposing a static search() method shaped like Laravel Scout's,
// without depending on the laravel/scout package being installed.
class ScoutSearchTestModel extends Model
{
    protected $table = 'test_models';

    public static function search(string $keyword): ScoutBuilderSpy
    {
        return new ScoutBuilderSpy($keyword);
    }
}

// Test Criteria implementation
class TestCriteria implements CriteriaInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function __construct(private readonly array $criteria = []) {}

    #[Override]
    public function toArray(): array
    {
        return $this->criteria;
    }
}

beforeEach(function (): void {
    // Create test table
    Schema::create('test_models', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('status')->default('active');
        $table->unsignedBigInteger('customer_id')->nullable();
        $table->timestamps();
    });

    Schema::create('test_customers', function ($table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    // Create test data
    TestModel::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    TestModel::create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    TestModel::create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);
});

describe('AbstractRepository Constructor', function (): void {
    it('can be instantiated without criteria', function (): void {
        $repository = new TestRepository();

        expect($repository)->toBeInstanceOf(TestRepository::class);
    });

    it('can be instantiated with criteria', function (): void {
        $criteria = new TestCriteria(['search' => 'john']);
        $repository = new TestRepository($criteria);

        expect($repository)->toBeInstanceOf(TestRepository::class);
    });
});

describe('Search functionality', function (): void {
    it('can search by keyword', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('doe')->get();

        expect($results)->toHaveCount(1);

        $results->each(function (TestModel $model): void {
            expect($model->getAttributeValue('name'))->toBe('John Doe');
        });
    });

    it('performs case insensitive search', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('DOE')->get();

        expect($results)->toHaveCount(1);
        $results->each(function (TestModel $model): void {
            expect($model->getAttributeValue('name'))->toBe('John Doe');
        });
    });

    it('searches across multiple columns', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('jane@example.com')->get();

        expect($results)->toHaveCount(1);
        $results->each(function (TestModel $model): void {
            expect($model->getAttributeValue('name'))->toBe('Jane Smith');
        });
    });

    it('returns empty collection when no matches found', function (): void {
        $repository = new TestRepository();
        $results = $repository->search('nonexistent')->get();

        expect($results)->toHaveCount(0);
    });

    it('handles empty search keyword gracefully', function (): void {
        $repository = new TestRepository();
        $results = $repository->search('')->get();

        expect($results)->toHaveCount(3);
    });
});

describe('Searchable relations', function (): void {
    it('matches a row via a related model column', function (): void {
        $customer = TestCustomer::create(['name' => 'Acme Corp']);
        TestModel::where('name', 'Bob Johnson')->update(['customer_id' => $customer->id]);

        $repository = new TestRepositoryWithSearchableRelations();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('acme')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->name)->toBe('Bob Johnson');
    });

    it('matches either own columns or related columns (OR semantics)', function (): void {
        $customer = TestCustomer::create(['name' => 'Acme Corp']);
        TestModel::where('name', 'Bob Johnson')->update(['customer_id' => $customer->id]);

        $repository = new TestRepositoryWithSearchableRelations();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('john')->get();

        // Matches "John Doe" via own column and "Bob Johnson" via surname substring
        expect($results->pluck('name')->all())
            ->toEqualCanonicalizing(['John Doe', 'Bob Johnson']);
    });

    it('does not match unrelated rows', function (): void {
        $customer = TestCustomer::create(['name' => 'Acme Corp']);
        TestModel::where('name', 'Bob Johnson')->update(['customer_id' => $customer->id]);

        $repository = new TestRepositoryWithSearchableRelations();
        $results = $repository->search('nonexistent')->get();

        expect($results)->toHaveCount(0);
    });

    it('can search purely via a related model when own searchableColumns is empty', function (): void {
        $customer = TestCustomer::create(['name' => 'Acme Corp']);
        TestModel::where('name', 'Bob Johnson')->update(['customer_id' => $customer->id]);

        $repository = new TestRepositoryWithOnlySearchableRelations();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('acme')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->name)->toBe('Bob Johnson');
    });

    it('leaves existing repositories without searchableRelations unaffected', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->search('doe')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->name)->toBe('John Doe');
    });
});

describe('Sorting functionality', function (): void {
    it('can set sort column from allowed columns', function (): void {
        $repository = new TestRepository();
        $result = $repository->sortColumn('name');

        expect($result)->toBeInstanceOf(TestRepository::class);
    });

    it('ignores invalid sort columns', function (): void {
        $repository = new TestRepository();
        $repository->sortColumn('invalid_column');

        $results = $repository->get();

        // Should still use default sorting (created_at desc)
        expect($results)->toHaveCount(3);
    });

    it('can set sort order', function (): void {
        $repository = new TestRepository();
        $result = $repository->sortOrder(AbstractRepository::SORT_ORDER_ASC);

        expect($result)->toBeInstanceOf(TestRepository::class);
    });

    it('sorts in ascending order', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository
            ->sortColumn('name')
            ->sortOrder(AbstractRepository::SORT_ORDER_ASC)
            ->get();

        expect($results->first()->name)->toBe('Bob Johnson');
        expect($results->last()->name)->toBe('John Doe');
    });

    it('sorts in descending order', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository
            ->sortColumn('name')
            ->sortOrder(AbstractRepository::SORT_ORDER_DESC)
            ->get();

        expect($results->first()->name)->toBe('John Doe');
        expect($results->last()->name)->toBe('Bob Johnson');
    });
});

describe('Pagination functionality', function (): void {
    it('can set per page limit', function (): void {
        $repository = new TestRepository();
        $result = $repository->perPage(2);

        expect($result)->toBeInstanceOf(TestRepository::class);
    });

    it('paginates results correctly', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository->perPage(2)->paginate();

        expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($results->count())->toBe(2);
        expect($results->total())->toBe(3);
    });
});

describe('Limit functionality', function (): void {
    it('can set result limit', function (): void {
        $repository = new TestRepository();
        $repository->limit(2);

        $results = $repository->get();

        expect($results)->toHaveCount(2);
    });

    test('limit works with search', function (): void {
        // Add more test data
        TestModel::create(['name' => 'John Smith', 'email' => 'johnsmith@example.com']);

        $repository = new TestRepository();
        $repository->limit(1);

        $results = $repository->search('john')->get();

        expect($results)->toHaveCount(1);
    });
});

describe('Limit applied to pagination', function (): void {
    it('caps paginated results when limit is smaller than perPage', function (): void {
        $repository = new TestRepository();

        $results = $repository->perPage(10)->limit(2)->paginate();

        expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($results->count())->toBe(2);
        expect($results->perPage())->toBe(2);
    });

    it('does not widen perPage when limit is larger than perPage', function (): void {
        $repository = new TestRepository();

        $results = $repository->perPage(1)->limit(10)->paginate();

        expect($results->count())->toBe(1);
        expect($results->perPage())->toBe(1);
    });

    it('uses limit as perPage when perPage is not set', function (): void {
        $repository = new TestRepository();

        $results = $repository->limit(2)->paginate();

        expect($results->count())->toBe(2);
        expect($results->perPage())->toBe(2);
    });
});

describe('Method chaining', function (): void {
    it('allows method chaining for fluent interface', function (): void {
        $repository = new TestRepository();
        /** @var Collection<int,TestModel> $results */
        $results = $repository
            ->search('doe')
            ->sortColumn('name')
            ->sortOrder(AbstractRepository::SORT_ORDER_ASC)
            ->perPage(10)
            ->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->name)->toBe('John Doe');
    });
});

describe('Constants', function (): void {
    it('has correct sort order constants', function (): void {
        expect(AbstractRepository::SORT_ORDER_ASC)->toBe('asc');
        expect(AbstractRepository::SORT_ORDER_DESC)->toBe('desc');
    });
});

describe('Criteria integration', function (): void {
    it('applies criteria during construction', function (): void {
        // This test would need to be adapted based on your actual criteria implementation
        // and what methods you expect to be called via criteria
        $criteria = new TestCriteria([
            'search' => 'doe',
            'sort_column' => 'name',
            'sort_order' => 'asc',
        ]);

        $repository = new TestRepository($criteria);
        $results = $repository->get();

        // Verify that criteria was applied
        expect($results)->toHaveCount(1);
    });

    it('throws when a criteria property has no matching repository method in local/testing', function (): void {
        $criteria = new TestCriteria([
            'non_existent_method' => 'some value',
            'search' => 'doe',
        ]);

        expect(fn () => new TestRepository($criteria))
            ->toThrow(UnmappedCriteriaException::class);
    });

    it('silently ignores unmapped criteria properties outside local/testing', function (): void {
        app()->detectEnvironment(fn () => 'production');

        try {
            $criteria = new TestCriteria([
                'non_existent_method' => 'some value',
                'search' => 'doe',
            ]);

            $repository = new TestRepository($criteria);
            $results = $repository->get();

            expect($results)->toHaveCount(1);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    });
});

describe('Edge cases', function (): void {
    it('handles empty database gracefully', function (): void {
        TestModel::truncate();

        $repository = new TestRepository();
        $results = $repository->get();

        expect($results)->toHaveCount(0);
    });

    it('handles null searchable columns', function (): void {
        $repository = new class extends AbstractRepository
        {
            protected string $model = TestModel::class;

            protected ?array $searchableColumns = null;
        };

        $results = $repository->search('john')->get();

        // Should return all results since search is ignored
        expect($results)->toHaveCount(3);
    });

    it('handles empty searchable columns', function (): void {
        $repository = new class extends AbstractRepository
        {
            protected string $model = TestModel::class;

            protected ?array $searchableColumns = [];
        };

        $results = $repository->search('john')->get();

        // Should return all results since search is ignored
        expect($results)->toHaveCount(3);
    });
});

describe('Return types', function (): void {
    test('get method returns Collection', function (): void {
        $repository = new TestRepository();
        $results = $repository->get();

        expect($results)->toBeInstanceOf(Collection::class);
    });

    test('paginate method returns LengthAwarePaginator', function (): void {
        $repository = new TestRepository();
        $results = $repository->perPage(2)->paginate();

        expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
    });
});

describe('Limit method fluency', function (): void {
    test('limit method maintains fluent interface', function (): void {
        $repository = new TestRepository();

        $result = $repository->limit(2);

        expect($result)->toBeInstanceOf(TestRepository::class);
    });
});

describe('Sort order normalization', function (): void {
    test('sortOrder validates and normalizes input', function (): void {
        $repository = new TestRepository();

        $repository->sortOrder('ASC');

        $results = $repository->get();
        expect($results)->toHaveCount(3);

        $repository2 = new TestRepository();
        $repository2->sortOrder('DESC');

        $results2 = $repository2->get();
        expect($results2)->toHaveCount(3);

        // Invalid order should be ignored
        $repository3 = new TestRepository();
        $repository3->sortOrder('invalid');

        $results3 = $repository3->get();
        expect($results3)->toHaveCount(3);
    });
});

describe('Blank search keyword handling', function (): void {
    it('handles empty string search gracefully', function (): void {
        $repository = new TestRepository();

        $results = $repository->search('')->get();

        expect($results)->toHaveCount(3);
    });

    it('handles whitespace-only search gracefully', function (): void {
        $repository = new TestRepository();

        $results = $repository->search('   ')->get();

        expect($results)->toHaveCount(3);
    });
});

describe('Reset functionality', function (): void {
    test('reset method creates fresh query', function (): void {
        $repository = new TestRepository();

        // Apply some filters
        $repository->search('john')->sortColumn('name')->limit(1);
        $firstResults = $repository->get();
        expect($firstResults)->toHaveCount(1);

        // Reset and get all
        $repository->reset();
        $allResults = $repository->get();
        expect($allResults)->toHaveCount(3);
    });
});

describe('Scout toggling', function (): void {
    test('withScout method can enable scout', function (): void {
        $repository = new TestRepository();

        $result = $repository->withScout(true);

        expect($result)->toBeInstanceOf(TestRepository::class);
    });

    test('withScout method can disable scout', function (): void {
        $repository = new TestRepository();

        $result = $repository->withScout(false);

        expect($result)->toBeInstanceOf(TestRepository::class);
    });

    it('scout is disabled by default', function (): void {
        $repository = new TestRepository();

        // Scout should not be used even if model has Searchable trait
        // This tests that useScout is false by default
        $results = $repository->search('doe')->get();

        expect($results)->toHaveCount(1);
    });
});

describe('Scout search limit', function (): void {
    it('applies a limit above the Meilisearch default before fetching keys', function (): void {
        ScoutBuilderSpy::$capturedLimit = null;
        ScoutBuilderSpy::$resultKeys = [1, 2, 3];

        $repository = new class extends AbstractRepository
        {
            protected string $model = ScoutSearchTestModel::class;
        };

        $reflection = new ReflectionMethod($repository, 'searchWithScout');
        $reflection->invoke($repository, 'doe');

        expect(ScoutBuilderSpy::$capturedLimit)
            ->toBe(AbstractRepository::SCOUT_SEARCH_RESULT_LIMIT)
            ->toBeGreaterThan(20);
    });
});

describe('Scout relevance ordering', function (): void {
    afterEach(function (): void {
        ScoutBuilderSpy::$resultKeys = [1, 2, 3];
    });

    it('preserves Meilisearch ranking instead of the default sort', function (): void {
        // Ids 1=John Doe, 2=Jane Smith, 3=Bob Johnson (created in that order).
        // Rank Bob first, then John, then Jane - not the default created_at desc order.
        ScoutBuilderSpy::$resultKeys = [3, 1, 2];

        $repository = new class extends AbstractRepository
        {
            protected string $model = ScoutSearchTestModel::class;
        };

        $reflection = new ReflectionMethod($repository, 'searchWithScout');
        $reflection->invoke($repository, 'doe');

        /** @var Collection<int,TestModel> $results */
        $results = $repository->get();

        expect($results->pluck('name')->all())->toBe([
            'Bob Johnson',
            'John Doe',
            'Jane Smith',
        ]);
    });

    it('lets an explicit sort column override the relevance ordering', function (): void {
        ScoutBuilderSpy::$resultKeys = [3, 1, 2];

        $repository = new class extends AbstractRepository
        {
            protected string $model = ScoutSearchTestModel::class;

            protected ?array $sortableColumns = ['name'];
        };

        $reflection = new ReflectionMethod($repository, 'searchWithScout');
        $reflection->invoke($repository, 'doe');

        /** @var Collection<int,TestModel> $results */
        $results = $repository
            ->sortColumn('name')
            ->sortOrder(AbstractRepository::SORT_ORDER_ASC)
            ->get();

        expect($results->pluck('name')->all())->toBe([
            'Bob Johnson',
            'Jane Smith',
            'John Doe',
        ]);
    });
});
