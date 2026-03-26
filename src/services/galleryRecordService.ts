import { GalleryService, type GalleryImage } from '@/services/galleryService'

export type GalleryRecord = GalleryImage

export function getAllGalleryRecords(): GalleryRecord[] {
  return GalleryService.getGalleryImages()
}

export function deleteGalleryRecord(id: string): void {
  GalleryService.removeFromGallery(id)
}
