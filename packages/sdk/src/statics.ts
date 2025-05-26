export enum PluginSetting {
  CONTROLLED = "pluginControlled",
  WP_TEMPLATE = "wpTemplate",
}

export enum FilterOperator {
  EQ = "eq",
  NEQ = "neq",
  LT = "lt",
  LTE = "lte",
  GT = "gt",
  GTE = "gte",
  LIKE = "like",
  NOT_LIKE = "not_like",
  CONTAINS = "contains",
  IN = "in",
  NIN = "nin",
  BETWEEN = "between",
  EXISTS = "exists",
  NOT_EXISTS = "not_exists",
}

export const FILTER_OPERATORS = Object.values(FilterOperator);
