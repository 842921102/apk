/**
 * 将推荐/收藏正文拆成「说明性文字」与「带序号的操作步骤」，
 * 避免把「1. 今日运势」与真正的制作步骤混在同一条编号链里。
 */

const FORTUNE_INTRO_RES = [
  /【\s*今日运势/,
  /今日运势\s*[】\]]/,
  /【\s*运势/,
  /神秘解析/,
  /占卜理由/,
  /今日食命/,
  /运势菜/,
]

function looksLikeFortuneNarrative(text: string): boolean {
  const head = text.slice(0, 720).trim()
  return FORTUNE_INTRO_RES.some((re) => re.test(head))
}

/**
 * 接口偶发把「今日运势」长文放在 steps[0]，与真正操作步骤混编；
 * 用于占卜结果页等结构化 steps 列表的纠偏。
 */
export function detachFortuneNarrativeFromStepList(steps: string[]): {
  narrativeFromStep: string | null
  operationSteps: string[]
} {
  const cleaned = steps.map((s) => s.trim()).filter(Boolean)
  if (cleaned.length > 1 && looksLikeFortuneNarrative(cleaned[0])) {
    return { narrativeFromStep: cleaned[0], operationSteps: cleaned.slice(1) }
  }
  return { narrativeFromStep: null, operationSteps: cleaned }
}

function splitNumberedChunks(content: string): { parts: string[]; usedNumericSplit: boolean } {
  const numbered = content
    .split(/\s*(?:\d+[\.\、]\s*)/)
    .map((s) => s.trim())
    .filter(Boolean)
  if (numbered.length > 1) {
    return { parts: numbered, usedNumericSplit: true }
  }
  const lines = content
    .split(/\n+/)
    .map((line) => line.replace(/^\d+[\.\、]\s*/, '').trim())
    .filter(Boolean)
  return { parts: lines, usedNumericSplit: false }
}

function splitStepsFromSection(section: string): string[] {
  const { parts, usedNumericSplit } = splitNumberedChunks(section.trim())
  if (usedNumericSplit && parts.length > 1) return parts
  if (usedNumericSplit && parts.length === 1) return parts
  return parts
}

/** 去掉正文中与结构化食材重复的「食材：」行 */
export function stripEmbeddedIngredientsLine(text: string): string {
  return text.replace(/(?:^|\n)\s*食材\s*[：:][^\n]*/g, '\n').replace(/\n{3,}/g, '\n\n').trim()
}

export interface ParsedRecipeDetailBody {
  /** 运势、解析等非操作流程，单独展示、不参与步骤序号 */
  narrative: string | null
  /** 仅操作流程步骤，展示时从 1 开始编号 */
  steps: string[]
  /**
   * 为 true 时表示正文与下方步骤列表来自同一段结构化编号，
   * 折叠区可不重复整段粘贴。
   */
  suppressRecipeBodyDuplicate: boolean
}

/**
 * @param raw 后端/模型返回的正文（可能含 1.2.3. 与「步骤：」等）
 */
export function parseRecipeDetailDisplay(raw: string): ParsedRecipeDetailBody {
  const content = (raw || '').trim()
  if (!content) {
    return { narrative: null, steps: [], suppressRecipeBodyDuplicate: false }
  }

  const stepsHeader = content.match(/(?:^|\n)\s*(?:步骤|制作步骤)\s*[：:]\s*\n?/m)
  if (stepsHeader && stepsHeader.index != null) {
    const narrativePart = content.slice(0, stepsHeader.index).trim()
    const after = content.slice(stepsHeader.index + stepsHeader[0].length).trim()
    const steps = splitStepsFromSection(after)
    if (steps.length) {
      return {
        narrative: narrativePart || null,
        steps,
        suppressRecipeBodyDuplicate: true,
      }
    }
  }

  const { parts, usedNumericSplit } = splitNumberedChunks(content)
  if (usedNumericSplit && parts.length > 1 && looksLikeFortuneNarrative(parts[0])) {
    return {
      narrative: parts[0].trim(),
      steps: parts.slice(1).map((s) => s.trim()).filter(Boolean),
      suppressRecipeBodyDuplicate: true,
    }
  }

  return {
    narrative: null,
    steps: parts,
    suppressRecipeBodyDuplicate: usedNumericSplit && parts.length > 1,
  }
}
