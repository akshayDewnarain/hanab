# TODO: Adding Image Functionality to a Model (Backend to Frontend)

This is a step-by-step guide for adding image upload/display functionality to a model entity.

## Backend Steps

### 1. Model (Domain/General/Models/[Entity].php)

- [ ] Import required classes:
  - `use Spatie\Image\Enums\Fit;`
  - `use Spatie\MediaLibrary\HasMedia;`
  - `use Spatie\MediaLibrary\InteractsWithMedia;`
  - `use Spatie\MediaLibrary\MediaCollections\Models\Media;`
- [ ] Make model implement `HasMedia` interface
- [ ] Add `InteractsWithMedia` trait to the model
- [ ] Add `registerMediaCollections()` method:
  ```php
  public function registerMediaCollections(): void
  {
      $this->addMediaCollection('image')
          ->useDisk('public')
          ->singleFile();
  }
  ```
- [ ] Add `registerMediaConversions()` method:

  ```php
  public function registerMediaConversions(?Media $media = null): void
  {
      /** Square thumbnail for cards */
      $this->addMediaConversion('thumb')
          ->fit(Fit::Crop, 300, 300)
          ->format('webp')
          ->nonQueued();

      /** Larger web-optimized image */
      $this->addMediaConversion('web')
          ->fit(Fit::Contain, 1200, 1200)
          ->format('webp');
  }
  ```

### 2. Data Transfer Object (Domain/General/DataTransferObjects/[Entity]Data.php)

- [ ] Import `use Illuminate\Http\UploadedFile;`
- [ ] Add `public ?UploadedFile $image = null,` to constructor parameters

### 3. Create Request (Application/API/General/Requests/Create[Entity]Request.php)

- [ ] Override `rules()` method to add image validation:
  ```php
  public function rules(): array
  {
      return [
          ...parent::rules(),
          'image' => [
              'nullable',
              'image',
              'mimes:jpeg,png,jpg,webp',
              'max:2048',
          ],
      ];
  }
  ```
- [ ] Override `validated()` method to handle file upload:
  ```php
  public function validated($key = null, $default = null)
  {
      $data = parent::validated($key, $default);

      if ($this->hasFile('image')) {
          $data['image'] = $this->file('image');
      }

      return $data;
  }
  ```

### 4. Update Request (Application/API/General/Requests/Update[Entity]Request.php)

- [ ] Override `rules()` method to add image validation (same as Create Request)
- [ ] Override `validated()` method to handle file upload (same as Create Request)

### 5. Create Action (Domain/General/Actions/Create[Entity]Action.php)

- [ ] Import `use Illuminate\Http\UploadedFile;`
- [ ] Import `use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;`
- [ ] Import `use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;`
- [ ] Add `@throws FileDoesNotExist` and `@throws FileIsTooBig` annotations to `execute()` method
- [ ] Add return type hint `: [Entity]` to `execute()` method
- [ ] Add image saving logic after `$model->save();`:
  ```php
  if ($data->image instanceof UploadedFile) {
      $model->addMedia($data->image)->toMediaCollection('image');
  }
  ```

### 6. Update Action (Domain/General/Actions/Update[Entity]Action.php)

- [ ] Import `use Illuminate\Http\UploadedFile;`
- [ ] Add return type hint `: [Entity]` to `execute()` method
- [ ] Add image saving logic after `$model->update(...);`:
  ```php
  if ($data->image instanceof UploadedFile) {
      $model->addMedia($data->image)->toMediaCollection('image');
  }
  ```

### 7. API Resource (Application/API/General/Resources/[Entity]Resource.php)

- [ ] Import `use Illuminate\Http\Request;`
- [ ] Override `toArray()` method to include image:

  ```php
  public function toArray(Request $request): array
  {
      $media = $this->resource->getFirstMedia('image');

      return [
          ...parent::toArray($request),
          'image' => [
              'thumb_url' => $media?->getUrl('thumb'),
              'url'       => $media?->getUrl(),
          ],
      ];
  }
  ```

### 8. Public Resource (Application/Public/Resources/[Entity]Resource.php) - If needed

- [ ] Import `use Illuminate\Http\Request;`
- [ ] Override `toArray()` method to include image (same as API Resource)

### 9. Delete Image Action Controller (Application/API/General/Controllers/Actions/Delete[Entity]ImageActionController.php)

- [ ] Create new file extending `BaseDeleteImageActionController`
- [ ] Set `protected string $param = '[entity]';` (singular form)
- [ ] Set `protected function modelClass(): string { return [Entity]::class; }`

### 10. Routes (routes/api.php)

- [ ] Import the DeleteImageActionController
- [ ] Add route after the apiResource route:
  ```php
  Route::apiResourceImageActions(
      '[entities]',  // plural
      '[entity]',   // singular
      Delete[Entity]ImageActionController::class
  );
  ```

## Frontend Steps

### 11. Model TypeScript Class (src/models/general/[Entity].ts)

- [ ] Import `MediaURL` from `@/models/general/MediaURL`
- [ ] Import `AxiosRequestConfig, AxiosResponse` from `axios`
- [ ] Add `image?: MediaURL;` property to the class
- [ ] Add `removeImage()` method:
  ```typescript
  removeImage(id: number | string, config: AxiosRequestConfig = {}): Promise<AxiosResponse<void>> {
      return http.delete(`${this.getEntity()}/${id}/image`, config);
  }
  ```
- [ ] Import `UploadImageField` from `@/modules/models/forms/UploadImageField`
- [ ] Import `ComponentEnum` from `@/modules/enums/helpers/ComponentEnum`
- [ ] Add image column to `columns()` method:
  ```typescript
  new TableColumn({
      name: 'image',
      label: 'GENERAL_IMAGE',
      sortable: false,
      filterable: false,
      visible: true,
      quickFilter: false,
      component: {
          type: ComponentEnum.IMAGE,
      },
      width: 'fit',
  }),
  ```
- [ ] Add `UploadImageField` to `inputFields()` method:
  ```typescript
  new UploadImageField({
      name: 'image',
      label: 'GENERAL_IMAGE',
      required: false,
      placeholder: 'GENERAL_IMAGE_PLACEHOLDER',
      validators: {
          required: false,
      },
      maxFileSizeMB: 5,
      maxFiles: 1,
      multiple: false,
      accept: 'image/*',
      media: {
          thumb_url: this.image?.thumb_url ?? '',
          url: this.image?.url ?? '',
      },
      callback: async () => {
          if (!this.id) return false;
          await this.removeImage(this.id);
          this.image = undefined;
          return true;
      },
  }),
  ```
- [ ] Add image field to `detailSchematic()` method in appropriate panel

## Verification Checklist

- [ ] Backend model can store images
- [ ] Backend API returns image URLs (thumb_url and url)
- [ ] Backend public API returns image URLs (if applicable)
- [ ] Image deletion endpoint works
- [ ] Frontend model displays images in table/list view
- [ ] Frontend form allows image upload
- [ ] Frontend form allows image deletion
- [ ] Image appears in detail view
- [ ] Image conversions (thumb, web) are generated correctly

## Notes

- Image collection name is always `'image'` (singular)
- Media conversions: `thumb` (300x300 crop) and `web` (1200x1200 contain)
- All conversions use WebP format for optimization
- Frontend uses `MediaURL` type with `thumb_url` and `url` properties
- Image deletion callback in UploadImageField removes the image and clears the property
