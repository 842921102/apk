/** 与 Web `SauceRecipe` / `SaucePreference` 对齐，供后端与展示层共用 */

export type SauceCategory = 'spicy' | 'garlic' | 'sweet' | 'complex' | 'regional' | 'fusion'

export interface SauceStep {
  step: number
  description: string
  time?: number
  temperature?: string
  technique?: string
}

export interface SauceStorageInfo {
  method: string
  duration: string
  temperature: string
}

export interface SauceRecipe {
  id: string
  name: string
  category: SauceCategory
  ingredients: string[]
  steps: SauceStep[]
  makingTime: number
  difficulty: 'easy' | 'medium' | 'hard'
  tips: string[]
  storage: SauceStorageInfo
  pairings: string[]
  tags: string[]
  spiceLevel?: number
  sweetLevel?: number
  saltLevel?: number
  sourLevel?: number
  description?: string
}

export interface SaucePreference {
  spiceLevel: number
  sweetLevel: number
  saltLevel: number
  sourLevel: number
  useCase: string[]
  availableIngredients: string[]
}
