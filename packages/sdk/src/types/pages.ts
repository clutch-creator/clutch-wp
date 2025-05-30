import { WpPageType, WpPageView } from "../statics";

export type TWpTemplateViewArchive = {
  template: WpPageView.ARCHIVE;
};

export type TWpTemplateViewSingleAny = {
  template: WpPageView.SINGLE_ANY;
  slug?: string;
};

export type TWpTemplateViewSingleSpecific = {
  template: WpPageView.SINGLE_SPECIFIC;
  slug?: string;
};

export type TWpTemplateView =
  | TWpTemplateViewArchive
  | TWpTemplateViewSingleAny
  | TWpTemplateViewSingleSpecific;

export type TWpTemplatePostType = {
  type: WpPageType.POST_TYPE;
  name: string;
  path: string;
} & TWpTemplateView;

export type TWpTemplateTaxonomy = {
  type: WpPageType.TAXONOMY;
  name: string;
  path: string;
} & TWpTemplateView;

export type TWpTemplateFrontPage = {
  type: WpPageType.FRONT_PAGE;
  path: string;
};

export type TWpTemplateNone = {
  type: WpPageType.NONE;
  path: string;
};

export type TWpTemplateAuthor = {
  type: WpPageType.AUTHOR;
  path: string;
};

export type TWpTemplateSearch = {
  type: WpPageType.SEARCH;
  path: string;
};

export type TWpTemplateNotFound = {
  type: WpPageType.NOT_FOUND;
  path: string;
};

export type TWpTemplate =
  | TWpTemplatePostType
  | TWpTemplateTaxonomy
  | TWpTemplateFrontPage
  | TWpTemplateNone
  | TWpTemplateAuthor
  | TWpTemplateSearch
  | TWpTemplateNotFound;

export type TWpTemplateList = TWpTemplate[];
