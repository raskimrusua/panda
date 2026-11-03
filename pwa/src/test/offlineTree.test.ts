import { describe, it, expect } from 'vitest';
import { OFFLINE_TREE, findTreeCrop, walkTree } from '@/lib/offline-tree';

describe('offline decision tree', () => {
  it('ships the 4-crop pilot set', () => {
    expect(OFFLINE_TREE.map((c) => c.slug)).toEqual(['tomato', 'kale', 'maize', 'pawpaw']);
  });

  it('walks to a diagnosis from a leaf option', () => {
    const tomato = findTreeCrop('tomato')!;
    const leafOption = tomato.questions[0]!.options[0]!;

    const step = walkTree(tomato, leafOption);
    expect(step.diagnosis).toBe('early_blight');
    expect(step.nextQuestion).toBeUndefined();
  });

  it('routes to the next question from a branch option', () => {
    const tomato = findTreeCrop('tomato')!;
    const curlOption = tomato.questions[0]!.options[2]!;

    const step = walkTree(tomato, curlOption);
    expect(step.nextQuestion?.id).toBe('tomato_curl');
    expect(step.diagnosis).toBeUndefined();
  });

  it('resolves the wilt branch to its two diagnoses', () => {
    const tomato = findTreeCrop('tomato')!;
    const wilt = tomato.questions.find((q) => q.id === 'tomato_wilt')!;
    const [ooze, ring, unsure] = wilt.options;

    expect(walkTree(tomato, ooze!).diagnosis).toBe('bacterial_wilt');
    expect(walkTree(tomato, ring!).diagnosis).toBe('fusarium_wilt');
    expect(walkTree(tomato, unsure!).diagnosis).toBe('uncertain');
  });

  it('returns undefined for unknown crops', () => {
    expect(findTreeCrop('pineapple')).toBeUndefined();
  });
});
