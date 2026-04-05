import { request } from '@/api/http'
import type { RecipeDetailPayload } from '@/types/recommendationHistory'

export interface DishRecipeApiResponse {
  dish_recipe_id: number
  recipe_detail: RecipeDetailPayload
}

export async function apiGetDishRecipe(id: number): Promise<{ data: DishRecipeApiResponse }> {
  return request<{ data: DishRecipeApiResponse }>({
    url: `/api/me/dish-recipes/${id}`,
    method: 'GET',
  })
}
