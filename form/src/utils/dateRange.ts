// src/utils/dateRange.ts

// カレンダー入力（<input type="date">）の表示・入力可能範囲を、
// 「起点年からspanYears年間」で計算するためのヘルパー。
// 例: getYearRange(2026, 3) → { min: '2026-01-01', max: '2028-12-31', minYear: 2026, maxYear: 2028 }
//
// <input type="date">のmin/max属性に渡すと、ブラウザのカレンダーウィジェットで
// 範囲外の年月へ移動・選択できなくなる（対応具合はブラウザ実装に依存する）。
// あわせてreact-hook-formのvalidateにも同じ範囲を使うことで、
// 手入力で範囲外の日付を入れた場合もエラーにする。
export function getYearRange(baseYear: number, spanYears: number) {
  const minYear = baseYear
  const maxYear = baseYear + spanYears - 1
  return {
    min: `${minYear}-01-01`,
    max: `${maxYear}-12-31`,
    minYear,
    maxYear,
  }
}
