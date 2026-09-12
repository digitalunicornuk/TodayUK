export const minimumArticleWords=400;
export function articleWordCount(body:string){
 const article=body.split(/\n\s*Sources?\s*(?::|\n|$)/i)[0];
 return article.replace(/https?:\/\/\S+/g,'').trim().split(/\s+/u).filter(Boolean).length;
}
export function articleLengthError(body:string){const count=articleWordCount(body);return count<minimumArticleWords?`Article incomplete: ${count} of ${minimumArticleWords} words. Add ${minimumArticleWords-count} words of verified reporting before publishing. Source links do not count.`:null;}
