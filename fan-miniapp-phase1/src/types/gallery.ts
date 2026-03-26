/** 与 Web `GalleryImage` / `galleryService` 对齐 */

export interface GalleryItem {
  id: string
  url: string
  recipeName: string
  recipeId: string
  cuisine: string
  ingredients: string[]
  generatedAt: string
  prompt?: string
  userId?: string
  userEmail?: string
}
