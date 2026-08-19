<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use App\Model\User;
use SunnyPHP\Database\QueryBuilder;

final class DatabaseTest extends ApplicationTestCase
{
    public function testQueryBuilderCrud(): void
    {
        $app = $this->bootApp();
        $this->migrate($app);
        $db = $this->db($app);

        $id = $db->table('users')->insertGetId([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret',
        ]);

        $row = $db->table('users')->where('id', $id)->first();
        $this->assertSame('Ada', $row['name']);
        $this->assertSame(1, $db->table('users')->count());

        $db->table('users')->where('id', $id)->update(['name' => 'Ada Lovelace']);
        $this->assertSame('Ada Lovelace', $db->table('users')->where('id', $id)->value('name'));

        $this->assertInstanceOf(QueryBuilder::class, $db->table('users')->whereIn('id', [(int) $id]));
        $this->assertSame(1, $db->table('users')->whereIn('id', [(int) $id])->count());

        $db->table('users')->where('id', $id)->delete();
        $this->assertSame(0, $db->table('users')->count());
    }

    public function testModelCrud(): void
    {
        $app = $this->bootApp();
        $this->migrate($app);

        $user = User::create([
            'name' => 'Grace',
            'email' => 'grace@example.com',
            'password' => 'secret',
        ]);

        $this->assertNotNull($user->id);
        $found = User::find($user->id);
        $this->assertSame('Grace', $found?->name);

        $found->name = 'Grace Hopper';
        $found->save();
        $this->assertSame('Grace Hopper', User::find($user->id)?->name);

        $found->delete();
        $this->assertNull(User::find($user->id));
        $this->assertSame([], User::all());
    }
}
