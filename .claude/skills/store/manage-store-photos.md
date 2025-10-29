---
name: manage-store-photos
description: "Manage store photos with ordering and limits for TanaVitrine vitrines. Use when working with photo uploads, ordering, plan limits, or media management for stores."
---

# Manage Store Photos

## Instructions

### Check Plan Limits
Before uploading, check if team can upload more photos:
```php
if (!$team->canUploadPhotos($photoCount)) {
    return back()->withErrors(['photos' => 'Plan limit reached']);
}

$limit = $team->getPlanLimit('photos_per_vitrine', 10);
```

### Upload Photos
1. Validate file (type, size)
2. Check plan limit
3. Store via Media model
4. Attach to team with order

```php
$media = Media::create([
    'path' => $request->file('photo')->store('teams/photos', 'public'),
    'type' => 'photo',
]);

$team->photos()->attach($media->id, [
    'order' => $team->photos()->count(),
    'is_primary' => $team->photos()->count() === 0,
]);
```

### Reorder Photos
Update order field in pivot table:
```php
foreach ($request->order as $index => $photoId) {
    $team->photos()->updateExistingPivot($photoId, [
        'order' => $index
    ]);
}
```

### Set Primary Photo
```php
// Remove current primary
$team->photos()->updateExistingPivot($team->photos()->get(), ['is_primary' => false]);

// Set new primary
$team->photos()->updateExistingPivot($photoId, ['is_primary' => true]);
```

## Database Structure
- **Pivot table**: `team_media`
- **Fields**: `team_id`, `media_id`, `order`, `is_primary`
- **Relationship**: `Team::photos()` -> `belongsToMany(Media::class)`

## Plan Limits
Default limits in `HasPlanLimits` trait:
- Free: 10 photos
- Pro: 30 photos
- Premium: 100 photos

Check with: `$team->getPlanLimit('photos_per_vitrine', 10)`
