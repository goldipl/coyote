export enum Model {
  Topic = 'Topic',
  User = 'User',
  Microblog = 'Microblog',
  Job = 'Job',
  Wiki = 'Wiki'
}

export interface User {
  id: number;
  name: string;
  deleted_at?: Date;
  is_blocked: boolean;
  photo: string;
  posts?: number;
  created_at?: Date;
  visited_at?: Date;
  allow_smilies?: boolean;
  allow_count?: boolean;
  allow_sig?: boolean;
  location?: string;
  sig?: string;
}

export interface Media {
  thumbnail: string;
  url: string;
  name: string;
}

export interface Microblog {
  id: number | null;
  parent_id?: number;
  votes: number;
  is_voted: boolean;
  is_subscribed: boolean;
  is_sponsored: boolean;
  created_at: Date | null;
  updated_at: Date | null;
  html: string;
  text: string;
  url: string;
  comments: Microblog[];
  user: User | null
  media: Media[];
  editable?: boolean;
  comments_count?: number;
  voters?: string[];
}

interface PostPermission {
  write: boolean;
  delete: boolean;
  update: boolean;
  merge: boolean;
  adm_access: boolean;
}

export interface Post {
  id: number;
  user: User;
  created_at: Date | null;
  updated_at: Date | null;
  deleted_at: Date | null;
  text: string;
  html: string;
  score: number;
  url: string;
  is_read: boolean;
  is_locked: boolean;
  permissions: PostPermission;
  ip?: string;
}

export interface Links {
  first: string;
  last: string;
  prev?: string;
  next: string;
}

export interface Meta {
  current_page: number;
  from: number;
  last_page: number;
  path: string;
  per_page: number;
  to: number;
  total: number;
}

export interface Paginator {
  data: Microblog[];
  links: Links;
  meta: Meta;
}
