// src/types/form.ts

//
// 共通型
//

export type ActivityCategory = 'ボランティア活動' | 'スポーツ活動' | 'その他市民活動'

export type ExpenseRow = {
  id: string
  subject: string
  amount: number
  grantUsage: number
  memo: string
}

export type IncomeData = {
  grantRequest: number
  memberFees: number
  donations: number
  tickets: number
  incomeMemo: {
    grantRequest: string
    memberFees: string
    donations: string
    tickets: string
  }
}

export type BudgetData = {
  income: IncomeData
  expenses: ExpenseRow[]
  budgetNote: string
}

//
// 要望申請
//

export type Section1Data = {
  // 団体情報
  teamName: string
  teamNameKana: string
  teamPostalCode: string
  teamAddress: string
  establishedYear: string
  members: {
    under20: number
    age21to40: number
    age41to60: number
    over61: number
  }
  activityCategory: ActivityCategory

  // 助成歴・応募歴
  grantHistory: {
    thisFoundationCount: number
    thisFoundationLatestYear: string
    otherFoundationCount: number
    otherFoundationLatestYear: string
  }
  applicationHistory: {
    count: number
    latestYear: string
  }
  applicationRoute: string[]
  applicationRouteOther: string

  // 代表者情報
  representativeName: string
  representativeNameKana: string
  representativePhone: string
  representativeEmail: string

  // 担当者情報
  sameAsRepresentative: boolean
  contactName: string
  contactNameKana: string
  contactPhone: string
  contactEmail: string
}

export type Section2Data = {
  // 事業基本情報
  projectName: string
  startDate: string
  endDate: string
  venue: string
  recruitmentArea: string

  // 参加人数
  organizer: {
    count: number
    days: number
    total: number
  }
  participants: {
    count: number
    days: number
    total: number
  }

  // 事業内容
  projectDetail: string
  projectPurpose: string
  projectPR: string

  // 共催・後援・協賛
  coOrganizers: string
}

export type Section3Data = BudgetData

export type Section4Data = {
  // 設立背景
  establishmentPurpose: string[]
  establishmentBackground: string

  // 日頃の活動
  activityFrequency: '毎週' | '月数回' | '月1回' | '年数回' | '不定期'
  activityContent: string

  // 実績・PR
  hasAward: 'あり' | 'なし'
  awardDetail: string
  hasCommunityInvolvement: 'あり' | 'なし'
  communityInvolvementDetail: string
  prNote: string
}

export type Section5Data = {
  photos: File[]
  docs: {
    regulations: FileList | null
    activityReport: FileList | null
    financialReport: FileList | null
    activityPlan: FileList | null
    financialPlan: FileList | null
  }
  confirmed: boolean
}

export type FormData = {
  section1: Section1Data
  section2: Section2Data
  section3: Section3Data
  section4: Section4Data
  section5: Section5Data
}

//
// 完了報告
//

export type ReportSection1Data = {
  teamName: string
  contactName: string
  contactPhone: string
  contactEmail: string
}

export type ReportSection2Data = {
  // 事業情報
  projectName: string
  activityCategory: ActivityCategory
  actualStartDate: string
  actualEndDate: string
  actualVenue: string
  organizerCount: number
  organizerDays: number
  participantCount: number
  participantDays: number
  actualDetail: string
} & BudgetData

export type ReportSection3Data = {
  photos: File[]
  receipts: File[]
  confirmed: boolean
}

export type ReportFormData = {
  reportSection1: ReportSection1Data
  reportSection2: ReportSection2Data
  reportSection3: ReportSection3Data
}
