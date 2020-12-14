import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.FileSystem;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;


public class InvertedIndexBigrams {

    public static class MyMapper extends Mapper<Object, Text, Text, Text>{

        private Text docId = new Text();
        private Text word = new Text();

        public void map(Object key, Text value, Context context
        ) throws IOException, InterruptedException {
            String previous = "";
            StringTokenizer itr = new StringTokenizer(value.toString());
            docId.set(itr.nextToken());
            while (itr.hasMoreTokens()) {
                String token = itr.nextToken().toLowerCase();
                token = token.replaceAll("[^a-zA-Z ]", " ");
                String[] tokens = token.split("\\s+");
                for(String val:tokens){
                    if(previous.equalsIgnoreCase("")){
                        previous = val;
                        continue;
                    }
                    String id = previous + " "+ val;
                    id = id.toLowerCase();
                    word.set(id);
                    context.write(word, docId);
                    previous = val;
                }
            }
        }
    }

    public static class MyReducer extends Reducer<Text,Text,Text,Text> {
        //private IntWritable result = new IntWritable();

        public void reduce(Text key, Iterable<Text> values,
                           Context context
        ) throws IOException, InterruptedException {

            Map docMap = new HashMap<String,Integer>();
            //int total = 0;

            for (Text val : values) {
                //total++;
                String vals = val.toString().toLowerCase();
                if (docMap.containsKey(vals)) {
                    docMap.put(vals, (Integer)(docMap.get(vals))+1);
                }
                else {
                    docMap.put(vals, 1);
                }
            }
            //String result = "total:" + total + " ";
            String result = "";
            for (Object docId : docMap.keySet()) {
                result += ((result.equals(""))?"":"\t") + docId.toString() + ":" + docMap.get(docId);
            }
            context.write(key, new Text(result));
        }
    }

    public static void main(String[] args) throws Exception {
        Configuration conf = new Configuration();
        Job job = Job.getInstance(conf, "Inverted Index");
        job.setJarByClass(InvertedIndexBigrams.class);
        job.setMapperClass(MyMapper.class);
        job.setReducerClass(MyReducer.class);
        job.setOutputKeyClass(Text.class);
        job.setOutputValueClass(Text.class);

        Path path = new Path(args[1]);
        FileSystem fileSystem = path.getFileSystem(conf);
        if (fileSystem.exists(path)) {
            fileSystem.delete(path, true);
        }

        FileInputFormat.addInputPath(job, new Path(args[0]));
        FileOutputFormat.setOutputPath(job, new Path(args[1]));
        System.exit(job.waitForCompletion(true) ? 0 : 1);
    }


}
