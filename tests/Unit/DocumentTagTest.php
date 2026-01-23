<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\DocumentTag;
use App\Models\DocumentArchive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_document_tag()
    {
        $tag = DocumentTag::factory()->create([
            'name' => 'Urgent',
            'slug' => 'urgent',
            'color' => '#ff0000',
        ]);

        $this->assertDatabaseHas('document_tags', [
            'name' => 'Urgent',
            'slug' => 'urgent',
            'color' => '#ff0000',
        ]);
    }

    #[Test]
    public function it_auto_generates_slug_from_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag = DocumentTag::create([
            'name' => 'Pending Review',
            'color' => '#FFA500',
        ]);

        $this->assertEquals('pending-review', $tag->slug);
    }

    #[Test]
    public function it_sets_created_by_and_updated_by_on_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag = DocumentTag::create([
            'name' => 'Test Tag',
            'color' => '#000000',
        ]);

        $this->assertEquals($user->id, $tag->created_by);
        $this->assertEquals($user->id, $tag->updated_by);
    }

    #[Test]
    public function it_has_many_to_many_relationship_with_documents()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);

        $this->assertTrue($tag->documents->contains($document));
        $this->assertEquals(1, $tag->documents->count());
    }

    #[Test]
    public function it_can_attach_multiple_documents()
    {
        $tag = DocumentTag::factory()->create(['name' => 'Urgent']);

        $doc1 = DocumentArchive::factory()->create();
        $doc2 = DocumentArchive::factory()->create();
        $doc3 = DocumentArchive::factory()->create();

        $tag->documents()->attach([$doc1->id, $doc2->id, $doc3->id]);

        $this->assertEquals(3, $tag->documents->count());
    }

    #[Test]
    public function it_can_detach_documents()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);
        $this->assertEquals(1, $tag->documents->count());

        $tag->documents()->detach($document);
        $this->assertEquals(0, $tag->documents()->count());
    }

    #[Test]
    public function document_can_have_multiple_tags()
    {
        $document = DocumentArchive::factory()->create();

        $urgentTag = DocumentTag::factory()->create(['name' => 'Urgent']);
        $verifiedTag = DocumentTag::factory()->create(['name' => 'Verified']);
        $confidentialTag = DocumentTag::factory()->create(['name' => 'Confidential']);

        $document->tags()->attach([$urgentTag->id, $verifiedTag->id, $confidentialTag->id]);

        $this->assertEquals(3, $document->tags->count());
        $this->assertTrue($document->tags->contains($urgentTag));
        $this->assertTrue($document->tags->contains($verifiedTag));
        $this->assertTrue($document->tags->contains($confidentialTag));
    }

    #[Test]
    public function it_syncs_tags_for_document()
    {
        $document = DocumentArchive::factory()->create();

        $tag1 = DocumentTag::factory()->create(['name' => 'Tag 1']);
        $tag2 = DocumentTag::factory()->create(['name' => 'Tag 2']);
        $tag3 = DocumentTag::factory()->create(['name' => 'Tag 3']);

        // Initial tags
        $document->tags()->sync([$tag1->id, $tag2->id]);
        $this->assertEquals(2, $document->tags->count());

        // Sync with different tags
        $document->tags()->sync([$tag2->id, $tag3->id]);
        $this->assertEquals(2, $document->tags()->count());
        $this->assertFalse($document->tags->contains($tag1));
        $this->assertTrue($document->tags->contains($tag2));
        $this->assertTrue($document->tags->contains($tag3));
    }

    #[Test]
    public function it_cascades_delete_on_pivot_table()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);

        $this->assertDatabaseHas('document_tag_pivot', [
            'tag_id' => $tag->id,
            'document_id' => $document->id,
        ]);

        // Delete the tag
        $tag->delete();

        $this->assertDatabaseMissing('document_tag_pivot', [
            'tag_id' => $tag->id,
            'document_id' => $document->id,
        ]);
    }

    #[Test]
    public function it_cascades_delete_when_document_is_deleted()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);

        $this->assertDatabaseHas('document_tag_pivot', [
            'tag_id' => $tag->id,
            'document_id' => $document->id,
        ]);

        // Delete the document
        $document->forceDelete();

        $this->assertDatabaseMissing('document_tag_pivot', [
            'tag_id' => $tag->id,
            'document_id' => $document->id,
        ]);
    }

    #[Test]
    public function it_searches_tags_by_name()
    {
        DocumentTag::factory()->create(['name' => 'Urgent']);
        DocumentTag::factory()->create(['name' => 'Verified']);
        DocumentTag::factory()->create(['name' => 'Pending Review']);

        $results = DocumentTag::search('Urgent')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('Urgent', $results->first()->name);
    }

    #[Test]
    public function it_searches_tags_by_slug()
    {
        DocumentTag::factory()->create(['name' => 'Urgent', 'slug' => 'urgent']);
        DocumentTag::factory()->create(['name' => 'Verified', 'slug' => 'verified']);

        $results = DocumentTag::search('urgent')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('Urgent', $results->first()->name);
    }

    #[Test]
    public function tag_name_is_unique()
    {
        DocumentTag::factory()->create(['name' => 'Urgent']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentTag::factory()->create(['name' => 'Urgent']);
    }

    #[Test]
    public function tag_slug_is_unique()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        DocumentTag::create(['name' => 'Test', 'slug' => 'test', 'color' => '#000']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DocumentTag::create(['name' => 'Test 2', 'slug' => 'test', 'color' => '#000']);
    }

    #[Test]
    public function it_has_default_color()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tag = DocumentTag::create([
            'name' => 'No Color Tag',
        ]);

        $this->assertEquals('#3b82f6', $tag->color);
    }

    #[Test]
    public function pivot_table_has_timestamps()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);

        $pivot = $tag->documents()->first()->pivot;

        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    #[Test]
    public function it_prevents_duplicate_tag_attachments()
    {
        $tag = DocumentTag::factory()->create();
        $document = DocumentArchive::factory()->create();

        $tag->documents()->attach($document);
        $tag->documents()->attach($document);

        // Should still only have 1 attachment due to unique constraint
        $this->assertEquals(1, $tag->documents()->count());
    }

    #[Test]
    public function it_can_find_documents_with_specific_tag()
    {
        $urgentTag = DocumentTag::factory()->create(['name' => 'Urgent']);
        $verifiedTag = DocumentTag::factory()->create(['name' => 'Verified']);

        $doc1 = DocumentArchive::factory()->create();
        $doc2 = DocumentArchive::factory()->create();
        $doc3 = DocumentArchive::factory()->create();

        $doc1->tags()->attach($urgentTag);
        $doc2->tags()->attach($urgentTag);
        $doc3->tags()->attach($verifiedTag);

        $urgentDocuments = DocumentArchive::whereHas('tags', function ($query) use ($urgentTag) {
            $query->where('document_tags.id', $urgentTag->id);
        })->get();

        $this->assertEquals(2, $urgentDocuments->count());
        $this->assertTrue($urgentDocuments->contains($doc1));
        $this->assertTrue($urgentDocuments->contains($doc2));
        $this->assertFalse($urgentDocuments->contains($doc3));
    }

    #[Test]
    public function it_can_find_documents_with_multiple_tags()
    {
        $urgentTag = DocumentTag::factory()->create(['name' => 'Urgent']);
        $verifiedTag = DocumentTag::factory()->create(['name' => 'Verified']);

        $doc1 = DocumentArchive::factory()->create();
        $doc2 = DocumentArchive::factory()->create();

        $doc1->tags()->attach([$urgentTag->id, $verifiedTag->id]);
        $doc2->tags()->attach($urgentTag);

        // Documents with both Urgent AND Verified tags
        $documentsWithBoth = DocumentArchive::whereHas('tags', function ($query) use ($urgentTag) {
            $query->where('document_tags.id', $urgentTag->id);
        })->whereHas('tags', function ($query) use ($verifiedTag) {
            $query->where('document_tags.id', $verifiedTag->id);
        })->get();

        $this->assertEquals(1, $documentsWithBoth->count());
        $this->assertTrue($documentsWithBoth->contains($doc1));
        $this->assertFalse($documentsWithBoth->contains($doc2));
    }

    #[Test]
    public function it_can_count_documents_per_tag()
    {
        $urgentTag = DocumentTag::factory()->create(['name' => 'Urgent']);
        $verifiedTag = DocumentTag::factory()->create(['name' => 'Verified']);

        $doc1 = DocumentArchive::factory()->create();
        $doc2 = DocumentArchive::factory()->create();
        $doc3 = DocumentArchive::factory()->create();

        $doc1->tags()->attach($urgentTag);
        $doc2->tags()->attach($urgentTag);
        $doc3->tags()->attach($verifiedTag);

        $tagsWithCounts = DocumentTag::withCount('documents')->get();

        $urgentTagWithCount = $tagsWithCounts->firstWhere('id', $urgentTag->id);
        $verifiedTagWithCount = $tagsWithCounts->firstWhere('id', $verifiedTag->id);

        $this->assertEquals(2, $urgentTagWithCount->documents_count);
        $this->assertEquals(1, $verifiedTagWithCount->documents_count);
    }
}
